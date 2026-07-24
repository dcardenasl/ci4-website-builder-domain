<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Cms;

use App\Interfaces\Cms\BlockTypeServiceInterface;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

/**
 * Smoke tests for BlockTypeService. Extend with domain-specific assertions
 * as business rules accumulate in the service.
 *
 * @internal
 */
final class BlockTypeServiceTest extends CIUnitTestCase
{
    public function testServiceImplementsItsInterface(): void
    {
        $service = Services::blockTypeService(false);

        $this->assertInstanceOf(BlockTypeServiceInterface::class, $service);
    }
}
