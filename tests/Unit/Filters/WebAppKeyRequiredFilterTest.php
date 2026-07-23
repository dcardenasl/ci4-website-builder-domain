<?php

declare(strict_types=1);

namespace Tests\Unit\Filters;

use App\Filters\WebAppKeyRequiredFilter;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Test\CIUnitTestCase;
use Config\App;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(WebAppKeyRequiredFilter::class)]
final class WebAppKeyRequiredFilterTest extends CIUnitTestCase
{
    protected function tearDown(): void
    {
        putenv('WEB_API_KEY');
        parent::tearDown();
    }

    public function testFailsClosedWhenKeyIsNotConfigured(): void
    {
        putenv('WEB_API_KEY');

        $request = $this->makeRequest('anything');
        $filter  = new WebAppKeyRequiredFilter();

        $response = $filter->before($request);

        // Regression guard: an unconfigured WEB_API_KEY must deny, not let
        // every request through unauthenticated (the original bug — see
        // commit history / CLAUDE.md's fail-closed-gate pattern).
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testRejectsMissingAppKeyHeaderWhenConfigured(): void
    {
        putenv('WEB_API_KEY=configured-secret');

        $request = $this->makeRequest('');
        $filter  = new WebAppKeyRequiredFilter();

        $response = $filter->before($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testRejectsMismatchedAppKeyHeader(): void
    {
        putenv('WEB_API_KEY=configured-secret');

        $request = $this->makeRequest('wrong-secret');
        $filter  = new WebAppKeyRequiredFilter();

        $response = $filter->before($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testAllowsMatchingAppKeyHeader(): void
    {
        putenv('WEB_API_KEY=configured-secret');

        $request = $this->makeRequest('configured-secret');
        $filter  = new WebAppKeyRequiredFilter();

        $response = $filter->before($request);

        $this->assertNull($response);
    }

    private function makeRequest(string $appKey): \CodeIgniter\HTTP\IncomingRequest
    {
        $request = new \CodeIgniter\HTTP\IncomingRequest(
            new App(),
            \Config\Services::uri(),
            'php://input',
            new \CodeIgniter\HTTP\UserAgent()
        );

        if ($appKey !== '') {
            $request->setHeader('X-App-Key', $appKey);
        }

        return $request;
    }
}
