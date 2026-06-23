<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Cms;

use App\Controllers\Api\V1\Cms\PublicMenuController;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class PublicMenuControllerTest extends CIUnitTestCase
{
    public function testResolvePageUrlReturnsLocalizedRootForHome(): void
    {
        $controller = new PublicMenuController();
        $method = new \ReflectionMethod($controller, 'resolvePageUrl');
        $method->setAccessible(true);

        $this->assertSame('/', $method->invoke($controller, 'home'));
        $this->assertSame('/', $method->invoke($controller, '/home/'));
    }

    public function testResolvePageUrlPrefixesRegularSlugs(): void
    {
        $controller = new PublicMenuController();
        $method = new \ReflectionMethod($controller, 'resolvePageUrl');
        $method->setAccessible(true);

        $this->assertSame('/contacto', $method->invoke($controller, 'contacto'));
        $this->assertSame('/noticias/archivo', $method->invoke($controller, 'noticias/archivo'));
    }
}
