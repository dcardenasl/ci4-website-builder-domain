<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Cms;

use App\Interfaces\Cms\MenuItemServiceInterface;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

/**
 * Smoke tests for MenuItemService. Extend with domain-specific assertions
 * as business rules accumulate in the service.
 *
 * @internal
 */
final class MenuItemServiceTest extends CIUnitTestCase
{
    public function testServiceImplementsItsInterface(): void
    {
        $service = Services::menuItemService(false);

        $this->assertInstanceOf(MenuItemServiceInterface::class, $service);
    }
}
