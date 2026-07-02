<?php

declare(strict_types=1);

namespace Tests\Unit\DTO\Request\Cms;

use App\DTO\Request\Cms\CollectionCreateRequestDTO;
use App\DTO\Request\Cms\CollectionUpdateRequestDTO;
use App\Libraries\Cms\BlockTemplateNormalizer;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class CollectionRequestDTOTest extends CIUnitTestCase
{
    public function testBlockTemplateNormalizerCanonicalizesRawTemplates(): void
    {
        $rawTemplate = json_encode([
            'version' => '1.0',
            'blocks' => [
                [
                    'block_key' => 'rich_text',
                    'label' => 'Intro',
                    'help_text' => 'Guide',
                    'sort_order' => 99,
                    'required' => 'false',
                    'locked' => 'true',
                    'block_config_defaults' => [
                        'zeta' => 'last',
                        'alpha' => 'first',
                    ],
                ],
                [
                    'block_key' => 'image',
                    'sort_order' => 5,
                    'required' => true,
                    'locked' => false,
                    'block_config_defaults' => [],
                ],
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $normalized = BlockTemplateNormalizer::normalize($rawTemplate);

        $canonical = json_encode([
            'version' => '1.0',
            'blocks' => [
                [
                    'block_key' => 'rich_text',
                    'sort_order' => 1,
                    'required' => false,
                    'locked' => true,
                    'label' => 'Intro',
                    'help_text' => 'Guide',
                    'block_config_defaults' => [
                        'alpha' => 'first',
                        'zeta' => 'last',
                    ],
                ],
                [
                    'block_key' => 'image',
                    'sort_order' => 2,
                    'required' => true,
                    'locked' => false,
                    'block_config_defaults' => new \stdClass(),
                ],
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $this->assertSame($canonical, json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    public function testCollectionDtosCarryCollectionType(): void
    {
        $create = $this->hydrateDto(CollectionCreateRequestDTO::class, [
            'collection_type' => 'news',
            'collection_key' => 'news',
            'is_active' => 1,
            'requires_approval' => 0,
            'enables_categories' => 1,
            'enables_tags' => 1,
            'default_sitemap_priority' => '0.5',
            'default_changefreq' => 'weekly',
            'sort_order' => 4,
            'translations' => [],
        ]);

        $update = $this->hydrateDto(CollectionUpdateRequestDTO::class, [
            'collection_type' => 'news',
        ]);

        $this->assertSame('news', $create->toArray()['collection_type']);
        $this->assertSame('news', $update->toArray()['collection_type']);
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
