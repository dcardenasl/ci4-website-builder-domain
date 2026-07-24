<?php

declare(strict_types=1);

namespace App\Libraries\Cms;

use dcardenasl\Ci4ApiCore\Queue\QueueManagerInterface;

/**
 * Notifies the public website to invalidate its server-side cache after content changes.
 *
 * Called from afterStore/afterUpdate hooks in CMS services. Never throws — failures
 * are logged and discarded so content saves always succeed even if the web app is down.
 */
class CacheInvalidationClient
{
    private string $webUrl;
    private string $invalidateKey;

    private ?QueueManagerInterface $queueManager;

    private bool $dispatch;

    private string $queueName;

    public function __construct(
        string $webUrl = '',
        string $invalidateKey = '',
        ?QueueManagerInterface $queueManager = null,
        bool $dispatch = true,
        string $queueName = 'default',
    ) {
        $this->webUrl        = rtrim($webUrl ?: (string) env('WEB_CACHE_INVALIDATE_URL', ''), '/');
        $this->invalidateKey = $invalidateKey ?: (string) env('WEB_CACHE_INVALIDATE_KEY', '');
        $this->queueManager = $queueManager;
        $this->dispatch     = $dispatch;
        $this->queueName    = $queueName;
    }

    /**
     * Send a cache invalidation request to the web app.
     *
     * @param list<string> $scopes e.g. ['pages', 'menus']
     */
    public function invalidate(array $scopes): void
    {
        if ($this->dispatch && $this->queueManager !== null) {
            try {
                $this->queueManager->push(
                    \App\Jobs\CacheInvalidationJob::class,
                    ['scopes' => $scopes],
                    $this->queueName,
                );
            } catch (\Throwable $exception) {
                log_message('error', '[CacheInvalidationClient] Could not dispatch invalidation job: ' . $exception->getMessage());
            }

            return;
        }

        $this->invalidateNow($scopes);
    }

    /** Execute the HTTP invalidation, normally from the queue worker. */
    /** @param list<string> $scopes */
    public function invalidateNow(array $scopes): void
    {
        if ($this->webUrl === '' || $this->invalidateKey === '' || empty($scopes)) {
            return;
        }

        $payload = json_encode(['scopes' => $scopes]);
        if ($payload === false) {
            log_message('error', '[CacheInvalidationClient] json_encode() failed for scopes: ' . implode(',', $scopes));

            return;
        }

        $ch = curl_init($this->webUrl . '/cache/invalidate');
        if ($ch === false) {
            log_message('error', '[CacheInvalidationClient] curl_init() failed.');

            return;
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'X-Invalidate-Key: ' . $this->invalidateKey,
            ],
        ]);

        $raw    = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error  = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            log_message('error', '[CacheInvalidationClient] cURL error: ' . $error);

            return;
        }

        if ($status < 200 || $status >= 300) {
            log_message('warning', '[CacheInvalidationClient] HTTP ' . $status
                . ' for scopes [' . implode(',', $scopes) . ']: ' . substr((string) $raw, 0, 200));

            return;
        }

        log_message('info', '[CacheInvalidationClient] Invalidated [' . implode(',', $scopes) . ']');
    }
}
