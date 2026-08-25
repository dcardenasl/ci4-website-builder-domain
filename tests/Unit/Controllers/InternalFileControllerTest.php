<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use App\Controllers\Api\V1\Internal\InternalFileController;
use App\Libraries\Hub\HubClient;
use App\Services\Cms\FileUsageService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

/**
 * @internal
 */
final class InternalFileControllerTest extends CIUnitTestCase
{
    protected function tearDown(): void
    {
        Services::resetSingle('fileUsageService');
        Services::resetSingle('hubClient');
        parent::tearDown();
    }

    public function testUsageReturnsHubFileContract(): void
    {
        $service = $this->createMock(FileUsageService::class);
        $service->expects($this->once())
            ->method('getUsagesByHubFileId')
            ->with(42)
            ->willReturn([
                [
                    'source'      => 'domain',
                    'resource'    => 'pages',
                    'resource_id' => 7,
                    'role'        => 'og_image',
                    'label'       => 'Page image',
                ],
            ]);
        Services::injectMock('fileUsageService', $service);

        $response = (new InternalFileController())->usage(42);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'status' => 'success',
            'data'   => [
                'in_use' => true,
                'usages' => [[
                    'source'      => 'domain',
                    'resource'    => 'pages',
                    'resource_id' => 7,
                    'role'        => 'og_image',
                    'label'       => 'Page image',
                ]],
            ],
        ], json_decode((string) $response->getBody(), true));
    }

    public function testInvalidateCacheDelegatesToHubClient(): void
    {
        $hub = new class () extends HubClient {
            public int $invalidatedFileId = 0;

            public function __construct()
            {
            }

            public function invalidateFileMetaCache(int $fileId): void
            {
                $this->invalidatedFileId = $fileId;
            }
        };
        Services::injectMock('hubClient', $hub);

        $response = (new InternalFileController())->invalidateCache(42);

        $this->assertSame(42, $hub->invalidatedFileId);
        $this->assertSame([
            'status' => 'success',
            'data'   => ['invalidated' => true],
        ], json_decode((string) $response->getBody(), true));
    }
}
