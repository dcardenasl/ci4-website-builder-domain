<?php

declare(strict_types=1);

namespace Tests\Support\Traits;

/**
 * Configures a fixed WEB_API_KEY and sends it as X-App-Key on every request
 * made via FeatureTestTrait for the duration of the test.
 *
 * WebAppKeyRequiredFilter fails closed (403/401) unless the caller sends a
 * matching X-App-Key header, so Feature tests exercising `public/*` CMS
 * routes need this. env('WEB_API_KEY') reads $_ENV, then $_SERVER, then
 * getenv() (CI4's Common.php env() helper), and CI4's DotEnv bootstrap loads
 * a real clone's .env into both superglobals — so all three must be managed
 * together, matching WebAppKeyRequiredFilterTest's pattern.
 */
trait WithWebAppKeyTrait
{
    private const TEST_WEB_API_KEY = 'test-web-api-key-fixture';

    private bool $webAppKeyHadOriginalValue;
    private string|false $webAppKeyOriginalValue;

    protected function configureWebAppKey(): void
    {
        $this->webAppKeyHadOriginalValue = array_key_exists('WEB_API_KEY', $_ENV);
        $this->webAppKeyOriginalValue    = $_ENV['WEB_API_KEY'] ?? false;

        $_ENV['WEB_API_KEY']    = self::TEST_WEB_API_KEY;
        $_SERVER['WEB_API_KEY'] = self::TEST_WEB_API_KEY;
        putenv('WEB_API_KEY=' . self::TEST_WEB_API_KEY);

        $this->withHeaders($this->webAppKeyHeader());
    }

    /**
     * withHeaders() replaces the full header set rather than merging, so any
     * test that calls it again after setUp() (e.g. to add Accept-Language)
     * must merge this in explicitly:
     * $this->withHeaders([...other, ...$this->webAppKeyHeader()])->get(...).
     *
     * @return array<string, string>
     */
    protected function webAppKeyHeader(): array
    {
        return ['X-App-Key' => self::TEST_WEB_API_KEY];
    }

    protected function restoreWebAppKey(): void
    {
        if ($this->webAppKeyHadOriginalValue) {
            $_ENV['WEB_API_KEY']    = $this->webAppKeyOriginalValue;
            $_SERVER['WEB_API_KEY'] = $this->webAppKeyOriginalValue;
            putenv('WEB_API_KEY=' . $this->webAppKeyOriginalValue);
        } else {
            unset($_ENV['WEB_API_KEY'], $_SERVER['WEB_API_KEY']);
            putenv('WEB_API_KEY');
        }
    }
}
