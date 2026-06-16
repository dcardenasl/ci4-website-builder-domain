<?php

declare(strict_types=1);

namespace Tests\Unit\DTO\Response\Cms;

use App\DTO\Response\Cms\EntryResponseDTO;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class EntryResponseDTOTest extends CIUnitTestCase
{
    public function testFromArrayExportsTopLevelTitleAndSlug(): void
    {
        $dto = EntryResponseDTO::fromArray([
            'id' => 1,
            'collection_id' => 3,
            'title' => 'Macbeth',
            'slug' => 'macbeth',
            'workflow_status' => 'draft',
            'is_featured' => false,
            'view_count' => 0,
            'sort_order' => 0,
            'is_in_sitemap' => false,
            'translations' => [],
        ]);

        $this->assertSame('Macbeth', $dto->title);
        $this->assertSame('macbeth', $dto->slug);
        $this->assertSame('Macbeth', $dto->toArray()['title']);
        $this->assertSame('macbeth', $dto->toArray()['slug']);
    }
}
