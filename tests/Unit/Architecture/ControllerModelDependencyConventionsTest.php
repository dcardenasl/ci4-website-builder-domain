<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Guardrail against Controllers bypassing the Service layer to talk to
 * Models/DB directly (audit 2026-07-21; remediation plan tracked as
 * DOM-112..DOM-124 in TASKS.md).
 *
 * Detects the same three patterns as ServiceModelDependencyConventionsTest,
 * scoped to app/Controllers instead of app/Services:
 *   - use_model:   `use App\Models\...;`                    (imported Model class)
 *   - model_call:  `model(\App\Models\X::class)` / `model('App\Models\X')` (inline FQCN resolution)
 *   - db_connect:  `Database::connect()`                      (direct DB connection)
 *
 * Started as a ratchet (BASELINE pinning 13 known violations across 9
 * controllers) and was drained to zero-tolerance in DOM-123, once DOM-113
 * through DOM-124 fixed every entry. BASELINE stays as the extension point:
 * any future violation must be added here deliberately (with a dated comment
 * explaining why), never silently — same philosophy as
 * ServiceModelDependencyConventionsTest and ServicePurityConventionsTest.
 */
class ControllerModelDependencyConventionsTest extends CIUnitTestCase
{
    /**
     * Known violations as of 2026-07-21 (measured with the same token-stripping
     * regex scan this test runs, not manual grep — do not add entries without a
     * matching architecture decision).
     *
     * @var array<string, array<string, int>>
     */
    private const BASELINE = [
        // WizardConfigController.php: fixed 2026-07-21 (DOM-122) — config() now
        // delegates to WizardConfigService::buildConfig(). Was the worst
        // offender (8 models resolved inline across 240 lines).
        // SettingConnectionController.php: fixed 2026-07-21 (DOM-115) — new
        // SettingConnectionService owns all data access now.
        // PublicMenuController.php: fixed 2026-07-21 (DOM-118) — show() now
        // delegates to MenuService::showPublic().
        // PublicCollectionController.php: fixed 2026-07-21 (DOM-119) — index()
        // now delegates to CollectionService::listPublic() via PublicCollectionReader.
        // BlockInstanceController.php: fixed 2026-07-21 (DOM-114) — the
        // instance->entry->collection->blockType lock-check moved to
        // BlockInstanceService::beforeDelete()/assertBlockNotLocked().
        // PublicPageController.php: fixed 2026-07-21 (DOM-120) — index()/show()
        // now delegate to PageService::listPublic()/showPublic() via
        // PublicPageReader. Preview-token verification (security-sensitive
        // ordering) stays in the controller unchanged; only the model queries
        // that ran after it moved.
        // PublicSettingController.php: fixed 2026-07-21 (DOM-117) — index() now
        // delegates to SettingService::listPublic().
        // PublicRedirectController.php: fixed 2026-07-21 (DOM-121) — resolve()
        // now delegates to RedirectService::resolvePublic() via the new
        // PublicRedirectResolver library (raw BaseConnection there, same
        // established pattern as TranslationResolver/OwnerUsageResolver, so
        // untracked by this guardrail — was the heaviest offender: previously
        // the only controller using Database::connect() directly, no Model at all.
        // PublicLanguageController.php: fixed 2026-07-21 (DOM-113) — index() now
        // delegates to LanguageService::listPublic(), no more direct model().
        // CategoryController.php/CollectionController.php/EntryController.php/
        // PageController.php: fixed 2026-07-21 (DOM-124) — checkSlug() now
        // delegates to the corresponding Service::isSlugAvailable(), which
        // wraps the same `new XTranslationModel()->isSlugAvailable()` call
        // the controller used to make directly.
        //
        // BASELINE is empty — every violation found in the 2026-07-21 audit
        // has been fixed. Any new entry here needs a deliberate architecture
        // decision, same as ServicePurityConventionsTest's zero-tolerance style.
    ];

    /** @var array<string, string> */
    private const PATTERNS = [
        'use_model' => '/^use\s+App\\\\Models\\\\/m',
        'model_call' => '/\bmodel\s*\(/',
        'db_connect' => '/\bDatabase\s*::\s*connect\s*\(/',
    ];

    public function testControllersDoNotGrowDirectModelOrDbCoupling(): void
    {
        $root = rtrim((string) ROOTPATH, DIRECTORY_SEPARATOR);
        $controllerDir = $root . DIRECTORY_SEPARATOR . 'app/Controllers';

        /** @var array<string, array<string, int>> $actual */
        $actual = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($controllerDir));
        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo || !$file->isFile() || !str_ends_with($file->getFilename(), '.php')) {
                continue;
            }

            $path = $file->getPathname();
            $source = file_get_contents($path);
            if (!is_string($source) || $source === '') {
                continue;
            }

            // Strip comments and string literals so text inside them can't trigger
            // a false positive, while preserving line breaks for multi-line regexes.
            $code = '';
            foreach (token_get_all($source) as $token) {
                if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT, T_CONSTANT_ENCAPSED_STRING], true)) {
                    $code .= str_repeat("\n", substr_count($token[1], "\n"));
                    continue;
                }
                $code .= is_array($token) ? $token[1] : $token;
            }

            $relative = str_replace('\\', '/', ltrim(str_replace($root, '', $path), DIRECTORY_SEPARATOR));

            foreach (self::PATTERNS as $name => $pattern) {
                $count = preg_match_all($pattern, $code);
                if ($count > 0) {
                    $actual[$relative][$name] = $count;
                }
            }
        }

        ksort($actual);

        $violations = [];
        foreach ($actual as $relative => $byPattern) {
            $baselineForFile = self::BASELINE[$relative] ?? null;
            if ($baselineForFile === null) {
                $violations[] = sprintf(
                    '%s: NEW file not in baseline (%s)',
                    $relative,
                    implode(', ', array_map(static fn (string $p, int $c): string => "{$p}={$c}", array_keys($byPattern), $byPattern))
                );
                continue;
            }

            foreach ($byPattern as $pattern => $count) {
                $allowed = $baselineForFile[$pattern] ?? 0;
                if ($count > $allowed) {
                    $violations[] = "{$relative}: {$pattern} count {$count} exceeds baseline {$allowed}";
                }
            }
        }

        $this->assertSame(
            [],
            $violations,
            "Controller layer Model/DB coupling grew beyond the pinned baseline:\n- " . implode("\n- ", $violations) . "\n\n" .
            'Controllers must delegate to a Service (see PublicEntryController for the reference pattern). If this ' .
            'is a justified exception, update ControllerModelDependencyConventionsTest::BASELINE deliberately — do ' .
            'not raise counts silently.'
        );
    }
}
