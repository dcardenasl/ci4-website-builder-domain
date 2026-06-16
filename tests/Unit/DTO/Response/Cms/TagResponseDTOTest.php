<?php

declare(strict_types=1);

namespace Tests\Unit\DTO\Response\Cms;

use App\DTO\Response\Cms\TagResponseDTO;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class TagResponseDTOTest extends CIUnitTestCase
{
    public function testFromArrayNormalizesTranslationsAndReadableFields(): void
    {
        $dto = TagResponseDTO::fromArray([
            'id' => 9,
            'name' => 'QA Tag',
            'slug' => 'qa-tag',
            'is_active' => true,
            'translations' => [
                ['language_id' => 2, 'name' => 'QA Tag', 'slug' => 'qa-tag'],
            ],
            'created_at' => Time::parse('2026-06-16 18:27:52'),
            'updated_at' => Time::parse('2026-06-16 18:28:52'),
        ]);

        $this->assertSame(9, $dto->id);
        $this->assertSame('QA Tag', $dto->name);
        $this->assertSame('qa-tag', $dto->slug);
        $this->assertTrue($dto->is_active);
        $this->assertSame('2026-06-16 18:27:52', $dto->createdAt);
        $this->assertSame('2026-06-16 18:28:52', $dto->updatedAt);
        $this->assertSame('QA Tag', $dto->translations[0]['name']);
    }
}
