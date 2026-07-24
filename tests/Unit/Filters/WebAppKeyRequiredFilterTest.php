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
    private bool $hadOriginalValue;
    private string|false $originalValue;

    protected function setUp(): void
    {
        parent::setUp();
        // env('WEB_API_KEY') reads $_ENV, then $_SERVER, then getenv() (see
        // Common.php's env() helper), and CI4's DotEnv bootstrap loads a
        // real clone's .env into BOTH $_ENV and $_SERVER — putenv() alone
        // (this codebase's usual filter-test pattern, e.g.
        // FeatureToggleFilterTest) only reaches getenv() and can't override
        // either superglobal. Save/restore both directly instead.
        $this->hadOriginalValue = array_key_exists('WEB_API_KEY', $_ENV);
        $this->originalValue    = $_ENV['WEB_API_KEY'] ?? false;
    }

    protected function tearDown(): void
    {
        if ($this->hadOriginalValue) {
            $_ENV['WEB_API_KEY']    = $this->originalValue;
            $_SERVER['WEB_API_KEY'] = $this->originalValue;
            putenv('WEB_API_KEY=' . $this->originalValue);
        } else {
            unset($_ENV['WEB_API_KEY'], $_SERVER['WEB_API_KEY']);
            putenv('WEB_API_KEY');
        }
        parent::tearDown();
    }

    private function setConfiguredKey(?string $key): void
    {
        if ($key === null) {
            unset($_ENV['WEB_API_KEY'], $_SERVER['WEB_API_KEY']);
            putenv('WEB_API_KEY');

            return;
        }

        $_ENV['WEB_API_KEY']    = $key;
        $_SERVER['WEB_API_KEY'] = $key;
        putenv('WEB_API_KEY=' . $key);
    }

    public function testFailsClosedWhenKeyIsNotConfigured(): void
    {
        $this->setConfiguredKey(null);

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
        $this->setConfiguredKey('configured-secret');

        $request = $this->makeRequest('');
        $filter  = new WebAppKeyRequiredFilter();

        $response = $filter->before($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testRejectsMismatchedAppKeyHeader(): void
    {
        $this->setConfiguredKey('configured-secret');

        $request = $this->makeRequest('wrong-secret');
        $filter  = new WebAppKeyRequiredFilter();

        $response = $filter->before($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testAllowsMatchingAppKeyHeader(): void
    {
        $this->setConfiguredKey('configured-secret');

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
