<?php

declare(strict_types=1);

namespace App\Libraries\Hub;

use dcardenasl\Ci4ApiCore\Http\Client\HubClient as CoreHubClient;
use dcardenasl\Ci4ApiCore\Http\RequestIdHolder;

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
        $fileIds = array_values(array_unique(array_filter(
            array_map(static fn (mixed $id): int => (int) $id, $fileIds),
            static fn (int $id): bool => $id > 0,
        )));
        if ($fileIds === []) {
            return [];
        }

        $cache    = $this->cache;
        $result   = [];
        $miss     = [];
        $staleTtl = max($cacheTtl, (int) env('HUB_FILE_META_STALE_TTL', 900));

        foreach ($fileIds as $id) {
            $cached = $cache->get($this->fileMetaCacheKey($id));
            if (is_array($cached)) {
                $result[$id] = $cached;
                continue;
            }

            $miss[] = $id;

            // The Hub may be temporarily unreachable for this batch — keep a
            // longer-lived stale copy so a transient outage degrades to
            // "slightly outdated metadata" instead of "no metadata at all".
            $stale = $cache->get($this->fileMetaStaleCacheKey($id));
            if (is_array($stale)) {
                $result[$id] = $stale;
            }
        }

        if ($miss === []) {
            return $result;
        }

        // The Hub's batch-meta endpoint caps a single request at 200 ids
        // (see InternalFileMetaController::batchMeta); requesting more than
        // that in one call would silently truncate the response and drop
        // the remaining ids instead of resolving them across a second call.
        foreach (array_chunk($miss, 200) as $batch) {
            try {
                $data = $this->requestPublicFileMetaBatch($batch);

                $items = is_array($data['data'] ?? null) ? $data['data'] : $data;

                foreach ($items as $fileId => $meta) {
                    if (! is_array($meta)) {
                        continue;
                    }
                    $id          = (int) $fileId;
                    $result[$id] = $meta;
                    $cache->save($this->fileMetaCacheKey($id), $meta, $cacheTtl);
                    $cache->save($this->fileMetaStaleCacheKey($id), $meta, $staleTtl);
                }
            } catch (\Throwable $e) {
                log_message('error', '[HubClient] resolvePublicFileMeta failed: ' . $e->getMessage());
            }
        }

        return $result;
    }

    /**
     * Use the shared client path by default. A direct request is available only
     * when both public-read timeout variables are explicitly configured, so a
     * clone retains the core retry/timeout behavior until an operator opts in.
     *
     * @param list<int> $batch
     * @return array<string, mixed>
     */
    private function requestPublicFileMetaBatch(array $batch): array
    {
        $timeouts = $this->publicReadTimeouts();
        if ($timeouts === null) {
            return $this->request('GET', '/api/v1/internal/files/batch-meta', [
                'headers' => $this->appKeyHeaders(),
                'query'   => ['ids' => $batch],
            ]);
        }

        $url       = rtrim($this->config->url, '/') . '/api/v1/internal/files/batch-meta';
        $headers   = $this->appKeyHeaders();
        $requestId = RequestIdHolder::get();
        if ($requestId !== null && ! array_key_exists('X-Request-Id', $headers)) {
            $headers['X-Request-Id'] = $requestId;
        }

        $startedAt = microtime(true);
        $status    = null;

        try {
            $response = $this->http->request('GET', $url, [
                'headers'         => $headers,
                'query'           => ['ids' => $batch],
                'connect_timeout' => $timeouts['connect_timeout'],
                'timeout'         => $timeouts['timeout'],
                'http_errors'     => false,
            ]);
            $status = $response->getStatusCode();
            $this->recordBreadcrumb('GET', $url, $status, (microtime(true) - $startedAt) * 1000, 1);

            if ($status < 200 || $status >= 300) {
                throw new \RuntimeException('Hub batch metadata request returned HTTP ' . $status . '.');
            }

            $decoded = json_decode((string) $response->getBody(), true);
            if (! is_array($decoded)) {
                throw new \RuntimeException('Hub batch metadata response was not a JSON object.');
            }

            return $decoded;
        } catch (\Throwable $exception) {
            if ($status === null) {
                $this->recordBreadcrumb('GET', $url, null, (microtime(true) - $startedAt) * 1000, 1);
            }

            throw $exception;
        }
    }

    /** @return array{connect_timeout: float, timeout: float}|null */
    private function publicReadTimeouts(): ?array
    {
        $connectTimeout = trim((string) env('PUBLIC_READ_HUB_CONNECT_TIMEOUT', ''));
        $requestTimeout = trim((string) env('PUBLIC_READ_HUB_TIMEOUT', ''));
        if ($connectTimeout === '' || $requestTimeout === '') {
            return null;
        }

        $connectSeconds = (float) $connectTimeout;
        $requestSeconds = (float) $requestTimeout;
        if ($connectSeconds <= 0 || $requestSeconds <= 0) {
            return null;
        }

        return [
            'connect_timeout' => $connectSeconds,
            'timeout'         => $requestSeconds,
        ];
    }

    /**
     * Invalidate cached file metadata for a given file ID.
     * Call this when the Hub notifies the Domain of a file update.
     */
    public function invalidateFileMetaCache(int $fileId): void
    {
        $cache = \Config\Services::cache();
        $cache->delete($this->fileMetaCacheKey($fileId));
        $cache->delete($this->fileMetaStaleCacheKey($fileId));
    }

    private function fileMetaCacheKey(int $fileId): string
    {
        return 'hub_file_meta_' . $fileId;
    }

    private function fileMetaStaleCacheKey(int $fileId): string
    {
        return 'hub_file_meta_stale_' . $fileId;
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
