<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Cms;

use App\Interfaces\Cms\RedirectServiceInterface;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;
use dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface;
use dcardenasl\Ci4ApiCore\Repositories\RepositoryInterface;

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
            ->with(['redirects']);

        $service = new \App\Services\Cms\RedirectService(
            $repository,
            $responseMapper,
            $cacheMock,
            $this->createMock(\App\Libraries\Cms\PublicRedirectResolver::class)
        );
        $result = $service->destroy(10, null);

        $this->assertTrue($result);
    }
}
