<?php

declare(strict_types=1);

namespace Tests\Unit\DTO\Request\Cms;

use App\DTO\Request\Cms\CollectionCreateRequestDTO;
use App\DTO\Request\Cms\CollectionUpdateRequestDTO;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class CollectionDynamicTypeTest extends CIUnitTestCase
{
    public function testCollectionCreateAcceptsDynamicSlugType(): void
    {
        $dto = (new \ReflectionClass(CollectionCreateRequestDTO::class))->newInstanceWithoutConstructor();
        $rules = $dto->rules();

        $this->assertSame('required|string|max_length[50]|regex_match[/^[a-z0-9]+(?:[-_][a-z0-9]+)*$/]', $rules['collection_type']);
    }

    public function testCollectionUpdateAcceptsDynamicSlugType(): void
    {
        $dto = (new \ReflectionClass(CollectionUpdateRequestDTO::class))->newInstanceWithoutConstructor();
        $rules = $dto->rules();

        $this->assertSame('permit_empty|string|max_length[50]|regex_match[/^[a-z0-9]+(?:[-_][a-z0-9]+)*$/]', $rules['collection_type']);
    }
}
