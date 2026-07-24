<?php

declare(strict_types=1);

namespace Tests\Unit\DTO\Response\Cms;

use App\DTO\Response\Cms\CategoryResponseDTO;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class CategoryResponseDTOTest extends CIUnitTestCase
{
    public function testFromArrayExportsTopLevelNameAndSlug(): void
    {
        $dto = CategoryResponseDTO::fromArray([
            'id' => 1,
            'collection_id' => 3,
            'name' => 'Arts',
            'slug' => 'arts',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $this->assertSame('Arts', $dto->name);
        $this->assertSame('arts', $dto->slug);
        $this->assertSame('Arts', $dto->toArray()['name']);
        $this->assertSame('arts', $dto->toArray()['slug']);
    }
}
