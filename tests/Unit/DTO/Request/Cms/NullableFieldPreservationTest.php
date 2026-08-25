<?php

declare(strict_types=1);

namespace Tests\Unit\DTO\Request\Cms;

use App\DTO\Request\Cms\BlockInstanceUpdateRequestDTO;
use App\DTO\Request\Cms\BlockTypeUpdateRequestDTO;
use App\DTO\Request\Cms\CategoryUpdateRequestDTO;
use App\DTO\Request\Cms\CollectionUpdateRequestDTO;
use App\DTO\Request\Cms\EntryUpdateRequestDTO;
use App\DTO\Request\Cms\RedirectUpdateRequestDTO;
use App\DTO\Request\Cms\TagUpdateRequestDTO;
use CodeIgniter\Test\CIUnitTestCase;
use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * @internal
 */
final class NullableFieldPreservationTest extends CIUnitTestCase
{
    /**
     * @return iterable<string, array{class-string<BaseRequestDTO>, array<string, mixed>, string}>
     */
    public static function explicitNullCases(): iterable
    {
        yield 'block config' => [BlockInstanceUpdateRequestDTO::class, ['block_config' => null], 'block_config'];
        yield 'block type description' => [BlockTypeUpdateRequestDTO::class, ['description' => null], 'description'];
        yield 'category parent' => [CategoryUpdateRequestDTO::class, ['parent_id' => null], 'parent_id'];
        yield 'collection template' => [CollectionUpdateRequestDTO::class, ['block_template' => null], 'block_template'];
        yield 'entry published at' => [EntryUpdateRequestDTO::class, ['published_at' => null], 'published_at'];
        yield 'redirect note' => [RedirectUpdateRequestDTO::class, ['note' => null], 'note'];
        yield 'tag active state' => [TagUpdateRequestDTO::class, ['is_active' => null], 'is_active'];
    }

    /** @param class-string<BaseRequestDTO> $class */
    #[DataProvider('explicitNullCases')]
    public function testExplicitNullIsRetainedInPayload(string $class, array $payload, string $field): void
    {
        $dto = new $class($payload, service('validation'));
        $data = $dto->toArray();

        $this->assertArrayHasKey($field, $data);
        $this->assertNull($data[$field]);
    }

    public function testAbsentNullableFieldsRemainOmitted(): void
    {
        $this->assertSame([], (new EntryUpdateRequestDTO([], service('validation')))->toArray());
        $this->assertSame([], (new CollectionUpdateRequestDTO([], service('validation')))->toArray());
    }
}
