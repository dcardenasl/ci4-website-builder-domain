<?php

declare(strict_types=1);

namespace Tests\Unit\DTO\Request\Cms;

use App\DTO\Request\Cms\MenuCreateRequestDTO;
use App\DTO\Request\Cms\MenuUpdateRequestDTO;
use CodeIgniter\Test\CIUnitTestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * @internal
 */
final class MenuRequestDTOTest extends CIUnitTestCase
{
    public function testCreateRequestAcceptsEmptyTranslationRowsAndKeepsCoreRules(): void
    {
        $reflection = new ReflectionClass(MenuCreateRequestDTO::class);
        /** @var MenuCreateRequestDTO $dto */
        $dto = $reflection->newInstanceWithoutConstructor();

        $this->assertSame('required|string|max_length[50]', $dto->rules()['menu_key']);
        $this->assertSame('permit_empty|is_natural_no_zero', $dto->rules()['translations.*.language_id']);

        $this->invokeMap($dto, [
            'menu_key' => 'qa-menu',
            'location' => 'header',
            'is_active' => true,
            'translations' => [
                ['language_id' => '', 'name' => ''],
                ['language_id' => '2', 'name' => 'Main Menu'],
            ],
        ]);

        $this->assertSame([
            'menu_key' => 'qa-menu',
            'location' => 'header',
            'is_active' => true,
            'translations' => [
                ['language_id' => 2, 'name' => 'Main Menu'],
            ],
        ], $dto->toArray());
    }

    public function testUpdateRequestAcceptsEmptyTranslationRowsAndKeepsOnlyMeaningfulPayload(): void
    {
        $reflection = new ReflectionClass(MenuUpdateRequestDTO::class);
        /** @var MenuUpdateRequestDTO $dto */
        $dto = $reflection->newInstanceWithoutConstructor();

        $this->assertSame('permit_empty|string|max_length[50]', $dto->rules()['menu_key']);
        $this->assertSame('permit_empty|is_natural_no_zero', $dto->rules()['translations.*.language_id']);

        $this->invokeMap($dto, [
            'menu_key' => 'qa-menu-updated',
            'translations' => [
                ['language_id' => '', 'name' => ''],
                ['language_id' => '3', 'name' => 'Footer Menu'],
            ],
        ]);

        $this->assertSame([
            'menu_key' => 'qa-menu-updated',
            'translations' => [
                ['language_id' => 3, 'name' => 'Footer Menu'],
            ],
        ], $dto->toArray());
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
