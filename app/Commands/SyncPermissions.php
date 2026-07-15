<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\DomainPermissions;
use Config\Hub as HubConfig;
use Config\Services;

/**
 * php spark domain:sync-permissions [--admin-token=<jwt>] [--assign-to-role=<ID|code>] [--mirror-to-self]
 *
 * Registers every permission listed in DomainPermissions::PERMISSIONS in the
 * hub's IAM using the website builder app's own X-App-Key (POST /api/v1/iam/self-permissions).
 * No superadmin JWT required for the primary registration.
 *
 * --admin-token is only required when:
 *   - --mirror-to-self is set (registers under hub app self, ID=1, for admin UI access)
 *   - --assign-to-role is set (links permissions to a non-superadmin role)
 */
class SyncPermissions extends BaseCommand
{
    protected $group       = 'Domain';
    protected $name        = 'domain:sync-permissions';
    protected $description = 'Register this app\'s permissions in the hub via its own API key (idempotent).';
    protected $usage       = 'domain:sync-permissions [--admin-token=<jwt>] [--assign-to-role=<ID|code>] [--mirror-to-self]';

    /** @var array<string, string> */
    protected $options = [
        '--admin-token'    => 'Superadmin JWT. Required only for --mirror-to-self or --assign-to-role.',
        '--assign-to-role' => 'Optionally link synced permissions to another role ID or code. Superadmin is attached by the hub.',
        '--mirror-to-self' => 'Also register the same permissions under hub app self (ID=1) for admin UI access.',
    ];

    private const SELF_APPLICATION_ID = 1;

    public function run(array $params): int
    {
        $mirrorToSelf = $this->shouldMirrorToSelf();

        if ($mirrorToSelf) {
            CLI::write('[DEPRECATED] --mirror-to-self is no longer needed. The hub resolves permissions across all applications via resolveAll(). This flag will be removed in the next release.', 'yellow');
        }
        $roleArg      = $this->resolveOption('assign-to-role');
        $roleArg      = is_string($roleArg) && $roleArg !== '' ? $roleArg : null;

        $needsToken = $mirrorToSelf || $roleArg !== null;
        $token      = $this->resolveAdminToken();

        if ($needsToken && $token === '') {
            $this->writeError('--admin-token is required when using --mirror-to-self or --assign-to-role.');
            $this->writeLine('Pass --admin-token=<jwt> or set hub.adminToken in .env.', 'yellow');
            $this->writeLine('Obtain one via: POST {hub.url}/api/v1/auth/login', 'cyan');

            return 1;
        }

        return $this->syncPermissions($mirrorToSelf, $roleArg, $token);
    }

    /**
     * @return int EXIT_SUCCESS|EXIT_ERROR
     */
    public function syncPermissions(bool $mirrorToSelf, ?string $roleArg = null, string $token = ''): int
    {
        $hub            = Services::hubClient();
        $permissions    = DomainPermissions::PERMISSIONS;
        $mirrorErrors   = 0;

        // Primary registration: website builder registers its own permissions via X-App-Key.
        // The hub assigns application_id from the key — no superadmin JWT needed.
        $this->writeLine(sprintf('Syncing %d permission(s) via self-permissions endpoint...', count($permissions)), 'cyan');

        try {
            $result         = $hub->registerSelfPermissions($permissions);
            $registered     = (int) ($result['created'] ?? 0);
            $existed        = (int) ($result['existing'] ?? 0);
            $errors         = (int) ($result['rejected'] ?? 0);
            $processedCodes = array_column($permissions, 'code');
        } catch (\Throwable $e) {
            $this->writeError(sprintf('Self-permissions sync failed: %s', $e->getMessage()));

            return 1;
        }

        if ($mirrorToSelf) {
            $this->newLine();
            $this->writeLine(sprintf('Mirroring permissions to hub app self (ID %d)...', self::SELF_APPLICATION_ID), 'cyan');

            foreach ($permissions as $permission) {
                try {
                    $created = $hub->registerPermission($permission, $token, self::SELF_APPLICATION_ID);
                    if ($created) {
                        $this->writeLine(sprintf('[+] %s (self)', $permission['code']), 'green');
                    } else {
                        $this->writeLine(sprintf('[=] %s (self already registered)', $permission['code']), 'yellow');
                    }
                } catch (\Throwable $e) {
                    $mirrorErrors++;
                    $this->writeError(sprintf('[!] %s (self) — %s', $permission['code'], $e->getMessage()));
                }
            }
        }

        // Automatic assignment to role
        $roleLinkFailed = false;
        if (is_string($roleArg) && $roleArg !== '') {
            $this->newLine();
            $this->writeLine(sprintf('Linking permissions to role: %s', $roleArg), 'cyan');

            try {
                $roleId = is_numeric($roleArg) ? (int) $roleArg : null;
                if ($roleId === null) {
                    $role = $hub->findRoleByCode($roleArg, $token);
                    if ($role === null) {
                        $this->writeError(sprintf('Role linking failed: %s not found — nothing attached.', $roleArg));
                        $roleLinkFailed = true;
                    } else {
                        $roleId = (int) $role['id'];
                    }
                }

                if ($roleId !== null) {
                    $hub->attachPermissionsToRole($roleId, $processedCodes, $token);
                    $this->writeLine(sprintf('Successfully linked %d permissions to role ID %d.', count($processedCodes), $roleId), 'green');
                }
            } catch (\Throwable $e) {
                $this->writeError(sprintf('Role linking failed: %s', $e->getMessage()));
                $roleLinkFailed = true;
            }
        }

        $this->newLine();
        if ($mirrorToSelf) {
            $this->writeLine(sprintf(
                'Self mirror: errors %d.',
                $mirrorErrors
            ), $mirrorErrors === 0 ? 'green' : 'yellow');
        }
        $this->writeLine(sprintf(
            'Done. Registered: %d, existed: %d, rejected: %d.',
            $registered,
            $existed,
            $errors
        ), ($errors === 0 && $mirrorErrors === 0) ? 'green' : 'yellow');

        // Automatic cache clearing for development environments (DX improvement)
        if (ENVIRONMENT === 'development') {
            $this->clearDevelopmentCaches();
        }

        return ($errors === 0 && $mirrorErrors === 0 && !$roleLinkFailed) ? 0 : 1;
    }

    protected function resolveAdminToken(): string
    {
        $flag = $this->resolveOption('admin-token');
        if (is_string($flag) && $flag !== '') {
            return $flag;
        }

        /** @var HubConfig $hubConfig */
        $hubConfig = config(HubConfig::class);

        return $hubConfig->adminToken;
    }

    protected function clearDevelopmentCaches(): void
    {
        $this->writeLine('Clearing local caches...', 'cyan');

        $localSpark = $this->localSparkPath();
        if ($localSpark !== null) {
            $this->runSparkCacheClear($localSpark);
        }
    }

    protected function runSparkCacheClear(string $sparkPath): void
    {
        @exec(PHP_BINARY . ' ' . escapeshellarg($sparkPath) . ' cache:clear');
    }

    private function localSparkPath(): ?string
    {
        $sparkPath = realpath(__DIR__ . '/../../spark');

        return ($sparkPath !== false && is_file($sparkPath)) ? $sparkPath : null;
    }

    protected function shouldMirrorToSelf(): bool
    {
        return $this->resolveOption('mirror-to-self') !== null;
    }

    /**
     * Resolve a CLI option supporting both formats:
     *   --option value   (CI4 native)
     *   --option=value   (stored by CI4 as the raw option key)
     *
     * @return string|true|null
     */
    protected function resolveOption(string $name)
    {
        $value = CLI::getOption($name);

        if ($value === null || $value === true) {
            foreach (CLI::getOptions() as $key => $val) {
                if (str_starts_with($key, "{$name}=")) {
                    return substr($key, strlen($name) + 1);
                }
            }
        }

        if ($value === true) {
            return true;
        }

        return $value;
    }

    protected function writeLine(string $message, string $color = 'white'): void
    {
        CLI::write($message, $color);
    }

    protected function writeError(string $message): void
    {
        CLI::error($message);
    }

    protected function newLine(int $repeat = 1): void
    {
        CLI::newLine($repeat);
    }
}
