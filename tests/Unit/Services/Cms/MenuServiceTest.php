<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Cms;

use App\Interfaces\Cms\MenuServiceInterface;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

/**
 * Smoke tests for MenuService. Extend with domain-specific assertions
 * as business rules accumulate in the service.
 *
 * @internal
 */
final class MenuServiceTest extends CIUnitTestCase
{
    public function testServiceImplementsItsInterface(): void
    {
        $service = Services::menuService(false);

        $this->assertInstanceOf(MenuServiceInterface::class, $service);
    }
}
