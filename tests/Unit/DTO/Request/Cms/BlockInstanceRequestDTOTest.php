<?php

declare(strict_types=1);

namespace Tests\Unit\DTO\Request\Cms;

use App\DTO\Request\Cms\BlockInstanceCreateRequestDTO;
use App\DTO\Request\Cms\BlockInstanceUpdateRequestDTO;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class BlockInstanceRequestDTOTest extends CIUnitTestCase
{
    public function testBlockDataIsOptionalAndArrayBasedOnBothContracts(): void
    {
        $createDto = $this->hydrateDto(BlockInstanceCreateRequestDTO::class, [
            'block_id' => 5,
            'owner_type' => 'page',
            'owner_id' => 21,
            'sort_order' => 1,
            'is_active' => true,
            'block_config' => '{"theme":"dark"}',
            'translations' => [
                [
                    'language_id' => 1,
                    'is_published' => true,
                ],
            ],
        ]);

        $updateDto = $this->hydrateDto(BlockInstanceUpdateRequestDTO::class, [
            'translations' => [
                [
                    'language_id' => 1,
                    'is_published' => true,
                ],
            ],
        ]);

        $this->assertSame('permit_empty|array', $createDto->rules()['translations.*.block_data']);
        $this->assertSame('permit_empty|array', $updateDto->rules()['translations.*.block_data']);
        $this->assertSame('page', $createDto->toArray()['owner_type']);
        $this->assertSame(['theme' => 'dark'], $createDto->toArray()['block_config']);
        $this->assertSame([], $updateDto->toArray()['translations'][0]['block_data'] ?? []);
    }

    public function testUpdateAcceptsEmptyTranslationsArrayWithoutValidationError(): void
    {
        // A block instance with no translation rows yet (e.g. freshly created,
        // or re-saved via the admin's reorder endpoint) legitimately sends
        // translations: []. CodeIgniter's Validation engine used to reject
        // this: for a genuinely empty array it synthesizes a single null value
        // keyed by the literal wildcard field name and validates that, so
        // required_with[translations] wrongly treated the empty array as
        // "present" and failed on the synthetic null. Constructing the DTO
        // for real (not via the reflection-based hydrateDto() bypass used
        // above) exercises the actual validate() path against a real
        // Validation instance.
        $dto = new BlockInstanceUpdateRequestDTO(
            ['translations' => []],
            \Config\Services::validation()
        );

        $this->assertSame([], $dto->toArray()['translations']);
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
