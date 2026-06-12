<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Cms;

use App\Interfaces\Cms\PageServiceInterface;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

/**
 * Smoke tests for PageService. Extend with domain-specific assertions
 * as business rules accumulate in the service.
 *
 * @internal
 */
final class PageServiceTest extends CIUnitTestCase
{
    public function testServiceImplementsItsInterface(): void
    {
        $service = Services::pageService(false);

        $this->assertInstanceOf(PageServiceInterface::class, $service);
    }
}
