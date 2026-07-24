<?php

declare(strict_types=1);

namespace Tests\Unit\DTO\Request\Cms;

use App\DTO\Request\Cms\MenuItemIndexRequestDTO;
use CodeIgniter\Test\CIUnitTestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * @internal
 */
final class MenuItemIndexRequestDTOTest extends CIUnitTestCase
{
    public function testIndexRequestKeepsMenuIdFilterInPayload(): void
    {
        $reflection = new ReflectionClass(MenuItemIndexRequestDTO::class);
        /** @var MenuItemIndexRequestDTO $dto */
        $dto = $reflection->newInstanceWithoutConstructor();

        $this->invokeMap($dto, [
            'page' => 1,
            'per_page' => 20,
            'search' => null,
            'sort' => 'sort_order',
            'menu_id' => 7,
        ]);

        $this->assertArrayHasKey('menu_id', $dto->rules());
        $this->assertSame(7, $dto->toArray()['filter']['menu_id']);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function invokeMap(object $dto, array $data): void
    {
        $method = new ReflectionMethod($dto, 'map');
        $method->setAccessible(true);
        $method->invoke($dto, $data);
    }
}
