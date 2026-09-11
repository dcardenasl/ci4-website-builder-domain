<?php

declare(strict_types=1);

namespace Tests\Unit\Filters;

use App\Filters\HubSignatureFilter;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\HTTP\URI;
use CodeIgniter\HTTP\UserAgent;
use CodeIgniter\Test\CIUnitTestCase;
use Config\App;

/**
 * @internal
 */
final class HubSignatureFilterTest extends CIUnitTestCase
{
    public function testAcceptsAValidHubSignature(): void
    {
        $timestamp = (string) time();
        $request   = $this->makeRequest('GET', '/api/v1/internal/files/42/usage', $timestamp, 'shared-secret');

        $this->assertNull($this->filter('shared-secret')->before($request));
    }

    public function testRejectsMissingSecretConfiguration(): void
    {
        $timestamp = (string) time();
        $request   = $this->makeRequest('GET', '/api/v1/internal/files/42/usage', $timestamp, 'shared-secret');

        $response = $this->filter('')->before($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testRejectsAnInvalidSignature(): void
    {
        $timestamp = (string) time();
        $request   = $this->makeRequest('GET', '/api/v1/internal/files/42/usage', $timestamp, 'wrong-secret');

        $response = $this->filter('shared-secret')->before($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(401, $response->getStatusCode());
    }

    private function filter(string $secret): HubSignatureFilter
    {
        return new class ($secret) extends HubSignatureFilter {
            public function __construct(private readonly string $secret)
            {
            }

            protected function hubSecret(): string
            {
                return $this->secret;
            }
        };
    }

    private function makeRequest(string $method, string $path, string $timestamp, string $secret): IncomingRequest
    {
        $request = new IncomingRequest(new App(), new URI('http://localhost' . $path), null, new UserAgent());
        $request->setMethod($method);
        $request->setHeader('X-Hub-Timestamp', $timestamp);
        $request->setHeader(
            'X-Hub-Signature',
            hash_hmac('sha256', $method . "\n" . $path . "\n" . $timestamp, $secret)
        );

        return $request;
    }
}
