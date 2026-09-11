<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use CodeIgniter\Test\CIUnitTestCase;
use Config\Cache;

/**
 * @internal
 */
final class CacheTest extends CIUnitTestCase
{
    private bool $hadOriginalValue;
    private string|false $originalValue;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hadOriginalValue = array_key_exists('CACHE_HANDLER', $_ENV);
        $this->originalValue    = $_ENV['CACHE_HANDLER'] ?? false;
    }

    protected function tearDown(): void
    {
        if ($this->hadOriginalValue) {
            $_ENV['CACHE_HANDLER']    = $this->originalValue;
            $_SERVER['CACHE_HANDLER'] = $this->originalValue;
            putenv('CACHE_HANDLER=' . $this->originalValue);
        } else {
            unset($_ENV['CACHE_HANDLER'], $_SERVER['CACHE_HANDLER']);
            putenv('CACHE_HANDLER');
        }

        parent::tearDown();
    }

    public function testUsesAnAppSpecificPrefixAndPortableFileDefault(): void
    {
        $config = new Cache();

        $this->assertSame('ci4_website_builder_domain_', $config->prefix);
        $this->assertSame('file', $config->handler);
        $this->assertArrayHasKey('apcu', $config->validHandlers);
    }

    public function testExplicitHandlerOverrideIsHonored(): void
    {
        $_ENV['CACHE_HANDLER']    = 'apcu';
        $_SERVER['CACHE_HANDLER'] = 'apcu';
        putenv('CACHE_HANDLER=apcu');

        $this->assertSame('apcu', (new Cache())->handler);
    }
}
