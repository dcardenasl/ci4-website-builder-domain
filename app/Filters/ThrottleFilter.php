<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use dcardenasl\Ci4ApiCore\Http\Filters\AbstractThrottleFilter;

/**
 * Rate-limiting filter for the website builder app.
 *
 * Public server-to-server reads are bucketed by the authenticated X-App-Key rather
 * than by the caller IP. The latter is shared by several sites in common hosting
 * layouts, while the app key is the actual caller identity. Writes and protected
 * routes retain the core IP/user buckets.
 */
class ThrottleFilter extends AbstractThrottleFilter
{
    /**
     * @return list<array{key: string, limit: int, window: int}>
     */
    protected function resolveBuckets(RequestInterface $request): array
    {
        if (strtolower($request->getMethod()) === 'get' && $this->isPublicRead($request)) {
            $appKey = trim($request->getHeaderLine('X-App-Key'));

            if ($appKey !== '') {
                return [[
                    'key'    => 'rate_limit_public_read_app_' . hash('sha256', $appKey),
                    'limit'  => max(1, (int) env('PUBLIC_READ_RATE_LIMIT_REQUESTS', 600)),
                    'window' => max(1, (int) env('PUBLIC_READ_RATE_LIMIT_WINDOW', 60)),
                ]];
            }
        }

        return parent::resolveBuckets($request);
    }

    private function isPublicRead(RequestInterface $request): bool
    {
        $path = $request->getUri()->getPath();

        return str_contains($path, '/api/v1/public/')
            || str_contains($path, '/api/v1/cms/public/')
            || str_contains($path, '/api/v1/public-read/');
    }
}
