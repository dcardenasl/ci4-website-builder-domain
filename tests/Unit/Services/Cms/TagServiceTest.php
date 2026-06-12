<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Cms;

use App\Interfaces\Cms\TagServiceInterface;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

/**
 * Smoke tests for TagService. Extend with domain-specific assertions
 * as business rules accumulate in the service.
 *
 * @internal
 */
final class TagServiceTest extends CIUnitTestCase
{
    public function testServiceImplementsItsInterface(): void
    {
        $service = Services::tagService(false);

        $this->assertInstanceOf(TagServiceInterface::class, $service);
    }
}
