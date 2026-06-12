<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Cms;

use App\Interfaces\Cms\RedirectServiceInterface;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

/**
 * Smoke tests for RedirectService. Extend with domain-specific assertions
 * as business rules accumulate in the service.
 *
 * @internal
 */
final class RedirectServiceTest extends CIUnitTestCase
{
    public function testServiceImplementsItsInterface(): void
    {
        $service = Services::redirectService(false);

        $this->assertInstanceOf(RedirectServiceInterface::class, $service);
    }
}
