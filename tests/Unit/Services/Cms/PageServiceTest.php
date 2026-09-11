<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Cms;

use App\Interfaces\Cms\PageServiceInterface;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface;
use dcardenasl\Ci4ApiCore\Repositories\RepositoryInterface;
use dcardenasl\Ci4ApiCore\Support\RequestDtoFactory;

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
            ->with(['pages', 'collections']);
        $referenceSynchronizer = $this->createMock(\App\Libraries\Cms\FileReferenceSynchronizer::class);
        $referenceSynchronizer->expects($this->once())
            ->method('removeResourceReferences')
            ->with('page', 10);
        $blockInstancePurger = $this->createMock(\App\Libraries\Cms\BlockInstancePurger::class);
        $blockInstancePurger->expects($this->once())
            ->method('purgeForOwner')
            ->with('page', 10);

        $service = new \App\Services\Cms\PageService(
            $repository,
            $responseMapper,
            $this->createMock(\App\Libraries\Cms\SlugRedirectRecorder::class),
            $cacheMock,
            $this->createMock(\App\Libraries\Cms\FileUrlResolver::class),
            $referenceSynchronizer,
            $this->createMock(\App\Services\Cms\PublicPageReader::class),
            $blockInstancePurger,
            new RequestDtoFactory()
        );
        $result = $service->destroy(10, null);

        $this->assertTrue($result);
    }

    public function testScopedFullIndexRebuildsRequestThroughDtoFactory(): void
    {
        $repository = $this->createMock(RepositoryInterface::class);
        $repository->expects($this->once())
            ->method('paginateCriteria')
            ->with(
                $this->callback(static function (array $criteria): bool {
                    return ($criteria['filter']['id']['in'] ?? null) === [42];
                }),
                1,
                20,
                $this->isType('callable')
            )
            ->willReturn([
                'data' => [(object) ['id' => 42]],
                'total' => 1,
                'page' => 1,
                'per_page' => 20,
            ]);

        $responseMapper = $this->createMock(ResponseMapperInterface::class);
        $responseMapper->expects($this->once())
            ->method('map')
            ->with($this->isInstanceOf(\stdClass::class))
            ->willReturn(\App\DTO\Response\Cms\PageResponseDTO::fromArray([
                'id' => 42,
                'page_type' => 'standard',
                'status' => 'draft',
            ]));

        $resourceAuthorization = $this->createMock(\App\Interfaces\Cms\ResourceAuthorizationInterface::class);
        $resourceAuthorization->expects($this->once())
            ->method('scopeCriteria')
            ->with('page', $this->isType('array'), $this->isInstanceOf(SecurityContext::class))
            ->willReturnCallback(static function (string $resource, array $criteria, SecurityContext $context): array {
                $criteria['scope_ids'] = [42];

                return $criteria;
            });
        $resourceAuthorization->expects($this->once())
            ->method('projectionCriteria')
            ->with('page', $this->isType('array'), $this->isInstanceOf(SecurityContext::class))
            ->willReturnArgument(1);

        $service = new \App\Services\Cms\PageService(
            $repository,
            $responseMapper,
            $this->createMock(\App\Libraries\Cms\SlugRedirectRecorder::class),
            $this->createMock(\App\Libraries\Cms\CacheInvalidationClient::class),
            $this->createMock(\App\Libraries\Cms\FileUrlResolver::class),
            $this->createMock(\App\Libraries\Cms\FileReferenceSynchronizer::class),
            $this->createMock(\App\Services\Cms\PublicPageReader::class),
            $this->createMock(\App\Libraries\Cms\BlockInstancePurger::class),
            new RequestDtoFactory(),
            null,
            null,
            $resourceAuthorization
        );

        $result = $service->index(
            new \App\DTO\Request\Cms\PageIndexRequestDTO([], service('validation')),
            new SecurityContext(user_id: 7)
        );

        $this->assertSame(1, $result->toArray()['total']);
    }
}
