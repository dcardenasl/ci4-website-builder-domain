<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Cms;

use App\Interfaces\Cms\SettingServiceInterface;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;
use dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface;
use dcardenasl\Ci4ApiCore\Repositories\RepositoryInterface;

/**
 * Smoke tests for SettingService.
 *
 * @internal
 */
final class SettingServiceTest extends CIUnitTestCase
{
    public function testServiceImplementsItsInterface(): void
    {
        $service = Services::settingService(false);

        $this->assertInstanceOf(SettingServiceInterface::class, $service);
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
            ->with(['settings']);
        $referenceSynchronizer = $this->createMock(\App\Libraries\Cms\FileReferenceSynchronizer::class);
        $referenceSynchronizer->expects($this->once())
            ->method('removeResourceReferences')
            ->with('setting', 10);

        $service = new \App\Services\Cms\SettingService(
            $repository,
            $responseMapper,
            $cacheMock,
            $referenceSynchronizer,
            $this->createMock(\App\Libraries\Cms\TranslationResolver::class),
            $this->createMock(\App\Libraries\Cms\FileUrlResolver::class),
            $this->createMock(\App\Libraries\Cms\PublicLocaleResolver::class),
            new \dcardenasl\Ci4ApiCore\Support\RequestDtoFactory()
        );
        $result = $service->destroy(10, null);

        $this->assertTrue($result);
    }
}
