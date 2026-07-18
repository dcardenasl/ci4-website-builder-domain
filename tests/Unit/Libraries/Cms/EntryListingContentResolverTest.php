<?php

declare(strict_types=1);

namespace Tests\Unit\Libraries\Cms;

use App\Libraries\Cms\BlockInstanceSerializer;
use App\Libraries\Cms\EntryListingContentResolver;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class EntryListingContentResolverTest extends CIUnitTestCase
{
    public function testSchemaSlotsOverrideBlockFallbacksAndBlocksSupplyMissingSlots(): void
    {
        $serializer = new class () extends BlockInstanceSerializer {
            public function __construct()
            {
            }

            public function forOwnersBatch(string $ownerType, array $ownerIds, string $langCode): array
            {
                return [
                    10 => [
                        ['block_key' => 'rich_text', 'block_data' => ['content' => '<p>Bloque</p>']],
                        [
                            'block_key' => 'image',
                            'block_config' => ['image' => ['source_kind' => 'external_url', 'file_id' => null, 'url' => '/block.jpg']],
                            'block_data' => ['alt' => 'Bloque imagen'],
                        ],
                        ['block_key' => 'cta', 'block_data' => ['label' => 'Bloque CTA', 'url' => '/bloque']],
                    ],
                    11 => [
                        ['block_key' => 'rich_text', 'block_data' => ['content' => '<p>Fallback</p>']],
                        [
                            'block_key' => 'image',
                            'block_config' => ['image' => ['source_kind' => 'external_url', 'file_id' => null, 'url' => '/fallback.jpg']],
                            'block_data' => ['alt' => 'Fallback imagen'],
                        ],
                        ['block_key' => 'cta', 'block_data' => ['label' => 'Fallback CTA', 'url' => '/fallback']],
                    ],
                ];
            }
        };

        $resolver = new EntryListingContentResolver($serializer);
        $content = $resolver->resolveBatch([
            [
                'id' => 10,
                'schema_data' => [
                    'listing' => [
                        'rich_text' => '<p>Schema</p>',
                        'image' => ['url' => '/schema.jpg', 'alt' => 'Schema imagen'],
                        'secondary_action' => ['label' => 'Schema CTA', 'url' => '/schema'],
                    ],
                ],
            ],
            ['id' => 11, 'schema_data' => []],
        ], 'es');

        $this->assertSame('<p>Schema</p>', $content[10]['rich_text']);
        $this->assertSame('/schema.jpg', $content[10]['image']['url']);
        $this->assertSame('Schema CTA', $content[10]['secondary_action']['label']);
        $this->assertSame('<p>Fallback</p>', $content[11]['rich_text']);
        $this->assertSame('/fallback.jpg', $content[11]['image']['url']);
        $this->assertSame('Fallback CTA', $content[11]['secondary_action']['label']);
    }
}
