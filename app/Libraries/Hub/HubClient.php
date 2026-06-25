<?php

declare(strict_types=1);

namespace App\Libraries\Hub;

use dcardenasl\Ci4ApiCore\Http\Client\HubClient as CoreHubClient;

/**
 * HTTP client subclass for the central hub (ci4-api-starter).
 *
 * Extends the core HubClient to add role management endpoints specific to domain apps.
 */
class HubClient extends CoreHubClient
{
    /**
     * Find a role by its unique code in the hub.
     *
     * @return array<string, mixed>|null
     */
    public function findRoleByCode(string $code, string $bearerToken): ?array
    {
        $data = $this->request('GET', '/api/v1/iam/roles', [
            'headers' => array_merge($this->appKeyHeaders(), [
                'Authorization' => 'Bearer ' . $bearerToken,
            ]),
            'query' => ['filter[code]' => $code, 'per_page' => 1],
        ]);

        $items = $data['items'] ?? $data;
        return is_array($items) ? ($items[0] ?? null) : null;
    }

    /**
     * Attach a list of permissions (by code) to a role (by ID) in the hub.
     *
     * @param list<string> $permissionCodes
     */
    public function attachPermissionsToRole(int $roleId, array $permissionCodes, string $bearerToken): void
    {
        if (empty($permissionCodes)) {
            return;
        }

        $this->request('POST', "/api/v1/iam/roles/{$roleId}/permissions/attach", [
            'headers' => array_merge($this->appKeyHeaders(), [
                'Authorization' => 'Bearer ' . $bearerToken,
            ]),
            'json' => ['permission_codes' => $permissionCodes],
        ]);
    }

    /**
     * Queue an email via the Hub's internal email endpoint.
     *
     * The Hub is the single email sender — Domain apps must never send emails directly.
     *
     * @return int Job ID (0 if queuing failed)
     */
    public function queueEmail(string $to, string $subject, string $message, ?string $textMessage = null): int
    {
        try {
            $data = $this->request('POST', '/api/v1/internal/email/queue', [
                'headers' => $this->appKeyHeaders(),
                'json'    => [
                    'to'           => $to,
                    'subject'      => $subject,
                    'message'      => $message,
                    'text_message' => $textMessage,
                ],
            ]);

            return (int) ($data['data']['job_id'] ?? 0);
        } catch (\Throwable $e) {
            log_message('error', '[HubClient] queueEmail failed: ' . $e->getMessage());
            return 0;
        }
    }
}
