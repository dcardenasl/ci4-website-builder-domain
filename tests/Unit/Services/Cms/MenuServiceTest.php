<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Cms;

use App\Interfaces\Cms\MenuServiceInterface;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;
use dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface;
use dcardenasl\Ci4ApiCore\Repositories\RepositoryInterface;

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
            ->with(['menus']);

        $service = new \App\Services\Cms\MenuService(
            $repository,
            $responseMapper,
            $cacheMock,
            $this->createMock(RepositoryInterface::class),
            $this->createMock(\App\Libraries\Cms\TranslationResolver::class),
            $this->createMock(\App\Interfaces\Cms\MenuItemServiceInterface::class)
        );
        $result = $service->destroy(10, null);

        $this->assertTrue($result);
    }
}
