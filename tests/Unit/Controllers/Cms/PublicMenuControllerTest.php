<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Cms;

use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class PublicMenuControllerTest extends CIUnitTestCase
{
    public function testResolvePageUrlReturnsLocalizedRootForHome(): void
    {
        $service = \Config\Services::menuItemService();
        $method = new \ReflectionMethod($service, 'resolvePageUrl');
        $method->setAccessible(true);

        $this->assertSame('/', $method->invoke($service, 'home'));
        $this->assertSame('/', $method->invoke($service, '/home/'));
    }

    public function testResolvePageUrlPrefixesRegularSlugs(): void
    {
        $service = \Config\Services::menuItemService();
        $method = new \ReflectionMethod($service, 'resolvePageUrl');
        $method->setAccessible(true);

        $this->assertSame('/contacto', $method->invoke($service, 'contacto'));
        $this->assertSame('/noticias/archivo', $method->invoke($service, 'noticias/archivo'));
    }
}
