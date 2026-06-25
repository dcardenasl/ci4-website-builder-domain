<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Validates that the X-App-Key header matches the configured WEB_API_KEY.
 *
 * Used on /api/v1/public/* routes so they are only callable by the Web app,
 * not directly from browsers or third parties.
 */
class WebAppKeyRequiredFilter implements FilterInterface
{
    /**
     * @param list<string>|null $arguments
     */
    public function before(RequestInterface $request, $arguments = null): ResponseInterface|null
    {
        $configuredKey = (string) env('WEB_API_KEY', '');
        if ($configuredKey === '') {
            return null;
        }

        $incomingKey = (string) $request->getHeaderLine('X-App-Key');

        if ($incomingKey === '' || ! hash_equals($configuredKey, $incomingKey)) {
            return \Config\Services::response()
                ->setStatusCode(401)
                ->setJSON([
                    'status'   => 'error',
                    'messages' => ['Unauthorized'],
                ]);
        }

        return null;
    }

    /**
     * @param list<string>|null $arguments
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): ResponseInterface|null
    {
        return null;
    }
}
