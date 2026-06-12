<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Cms;

use App\Interfaces\Cms\CollectionServiceInterface;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

/**
 * Smoke tests for CollectionService. Extend with domain-specific assertions
 * as business rules accumulate in the service.
 *
 * @internal
 */
final class CollectionServiceTest extends CIUnitTestCase
{
    public function testServiceImplementsItsInterface(): void
    {
        $service = Services::collectionService(false);

        $this->assertInstanceOf(CollectionServiceInterface::class, $service);
    }
}
