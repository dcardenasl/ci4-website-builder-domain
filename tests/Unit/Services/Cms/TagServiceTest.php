<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Cms;

use App\Interfaces\Cms\TagServiceInterface;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;
use dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface;
use dcardenasl\Ci4ApiCore\Repositories\RepositoryInterface;

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

    public function testDestroyInvalidatesCache(): void
    {
        $repository = $this->createMock(RepositoryInterface::class);
        $repository->expects($this->once())
            ->method('find')
            ->with(10)
            ->willReturn((object) ['id' => 10]);
        $repository->expects($this->once())
            ->method('setEntityContext')
            ->with(10, $this->isInstanceOf(\stdClass::class));
        $repository->expects($this->once())
            ->method('delete')
            ->with(10)
            ->willReturn(true);

        $responseMapper = $this->createMock(ResponseMapperInterface::class);
        $cacheMock = $this->createMock(\App\Libraries\Cms\CacheInvalidationClient::class);
        $cacheMock->expects($this->once())
            ->method('invalidate')
            ->with(['taxonomies', 'entries']);

        $service = new \App\Services\Cms\TagService(
            $repository,
            $responseMapper,
            $cacheMock,
            $this->createMock(\App\Libraries\Cms\TranslationResolver::class)
        );
        $result = $service->destroy(10, null);

        $this->assertTrue($result);
    }
}
