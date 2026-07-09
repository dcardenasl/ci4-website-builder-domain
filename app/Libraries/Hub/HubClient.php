<?php

declare(strict_types=1);

namespace App\Libraries\Hub;

use dcardenasl\Ci4ApiCore\Http\Client\HubClient as CoreHubClient;

/**
 * HTTP client subclass for the central hub (ci4-api-starter).
 *
 * Extends the core HubClient to add role management endpoints specific to the website builder app.
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
     * Batch-resolve public file metadata (id, url, variants) from the Hub.
     *
     * Results are cached using the CI4 cache store with a configurable TTL
     * (default 300 s). Already-cached IDs are not re-fetched.
     *
     * @param  list<int>  $fileIds
     * @param  int        $cacheTtl  Seconds to cache each file's metadata
     * @return array<int, array<string, mixed>>
     */
    public function resolvePublicFileMeta(array $fileIds, int $cacheTtl = 300): array
    {
        if (empty($fileIds)) {
            return [];
        }

        $cache  = \Config\Services::cache();
        $result = [];
        $miss   = [];

        foreach ($fileIds as $id) {
            $cached = $cache->get($this->fileMetaCacheKey($id));
            if (is_array($cached)) {
                $result[$id] = $cached;
            } else {
                $miss[] = $id;
            }
        }

        if (empty($miss)) {
            return $result;
        }

        try {
            $data = $this->request('GET', '/api/v1/internal/files/batch-meta', [
                'headers' => $this->appKeyHeaders(),
                'query'   => ['ids' => $miss],
            ]);

            $items = is_array($data['data'] ?? null) ? $data['data'] : $data;

            foreach ($items as $fileId => $meta) {
                if (! is_array($meta)) {
                    continue;
                }
                $id          = (int) $fileId;
                $result[$id] = $meta;
                $cache->save($this->fileMetaCacheKey($id), $meta, $cacheTtl);
            }
        } catch (\Throwable $e) {
            log_message('error', '[HubClient] resolvePublicFileMeta failed: ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * Invalidate cached file metadata for a given file ID.
     * Call this when the Hub notifies the Domain of a file update.
     */
    public function invalidateFileMetaCache(int $fileId): void
    {
        \Config\Services::cache()->delete($this->fileMetaCacheKey($fileId));
    }

    private function fileMetaCacheKey(int $fileId): string
    {
        return 'hub_file_meta_' . $fileId;
    }

    /**
     * Queue an email via the Hub's internal email endpoint.
     *
     * The Hub is the single email sender — website builder apps must never send emails directly.
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

            return (int) ($data['job_id'] ?? 0);
        } catch (\Throwable $e) {
            log_message('error', '[HubClient] queueEmail failed: ' . $e->getMessage());
            return 0;
        }
    }
}
