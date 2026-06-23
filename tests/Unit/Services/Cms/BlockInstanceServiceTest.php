<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Cms;

use App\DTO\Request\Cms\BlockInstanceUpdateRequestDTO;
use App\Interfaces\Cms\BlockInstanceServiceInterface;
use App\Services\Cms\BlockInstanceService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;
use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;
use dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface;
use dcardenasl\Ci4ApiCore\Repositories\RepositoryInterface;

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

    public function testUpdateSerializesBlockConfigBeforePersisting(): void
    {
        $repository = $this->createMock(RepositoryInterface::class);
        $repository->expects($this->once())
            ->method('setEntityContext')
            ->with(10, $this->isInstanceOf(\stdClass::class));
        $repository->expects($this->exactly(2))
            ->method('find')
            ->with(10)
            ->willReturnOnConsecutiveCalls(
                (object) ['id' => 10],
                (object) ['id' => 10, 'block_config' => ['theme' => 'dark']]
            );
        $repository->expects($this->once())
            ->method('update')
            ->with(10, $this->callback(static function (array $data): bool {
                return ($data['block_config'] ?? null) === '{"theme":"dark"}';
            }))
            ->willReturn(true);

        $responseMapper = $this->createMock(ResponseMapperInterface::class);
        $responseMapper->method('map')
            ->willReturn(new class () implements DataTransferObjectInterface {
                public function toArray(): array
                {
                    return ['id' => 10];
                }
            });

        $service = new BlockInstanceService($repository, $responseMapper);

        $dto = $this->hydrateDto(BlockInstanceUpdateRequestDTO::class, [
            'block_config' => ['theme' => 'dark'],
        ]);

        $result = $service->update(10, $dto, null);

        $this->assertSame(['id' => 10], $result->toArray());
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @param array<string, mixed> $data
     * @return T
     */
    private function hydrateDto(string $class, array $data): object
    {
        $reflection = new \ReflectionClass($class);
        /** @var object $dto */
        $dto = $reflection->newInstanceWithoutConstructor();

        $method = $reflection->getMethod('map');
        $method->setAccessible(true);
        $method->invoke($dto, $data);

        return $dto;
    }
}
