<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Cms;

use App\Interfaces\Cms\BlockInstanceServiceInterface;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

/**
 * Smoke tests for BlockInstanceService. Extend with domain-specific assertions
 * as business rules accumulate in the service.
 *
 * @internal
 */
final class BlockInstanceServiceTest extends CIUnitTestCase
{
    public function testServiceImplementsItsInterface(): void
    {
        $service = Services::blockInstanceService(false);

        $this->assertInstanceOf(BlockInstanceServiceInterface::class, $service);
    }
}
