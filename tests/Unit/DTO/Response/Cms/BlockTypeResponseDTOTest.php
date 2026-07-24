<?php

declare(strict_types=1);

namespace Tests\Unit\DTO\Response\Cms;

use App\DTO\Response\Cms\BlockTypeResponseDTO;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class BlockTypeResponseDTOTest extends CIUnitTestCase
{
    public function testFromArrayNormalizesTimeInstances(): void
    {
        $dto = BlockTypeResponseDTO::fromArray([
            'id' => 1,
            'block_key' => 'rich_text',
            'name' => 'Rich text',
            'description' => 'Text block',
            'category' => 'content',
            'icon' => 'file-text',
            'schema_definition' => [],
            'supports_pages' => true,
            'supports_entries' => true,
            'is_container' => false,
            'sort_order' => 0,
            'is_active' => true,
            'created_at' => Time::parse('2026-06-13 10:15:00'),
            'updated_at' => Time::parse('2026-06-13 10:20:00'),
        ]);

        $this->assertSame('2026-06-13 10:15:00', $dto->createdAt);
        $this->assertSame('2026-06-13 10:20:00', $dto->updatedAt);
    }
}
