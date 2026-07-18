<?php

declare(strict_types=1);

namespace Tests\Integration\Libraries;

use App\Libraries\Cms\BlockInstanceSerializer;
use App\Libraries\Cms\FileUrlResolver;
use App\Libraries\Hub\HubClient;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Database;

/**
 * @internal
 */
final class BlockInstanceSerializerTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    private BlockInstanceSerializer $serializer;

    protected function setUp(): void
    {
        parent::setUp();
        $resolver = new class () extends FileUrlResolver {
            public function __construct()
            {
            }

            public function collectBlockFileIds(array $blockData, array $schemaFields): array
            {
                $fileIds = [];
                foreach ($schemaFields as $fieldKey => $fieldDef) {
                    $type = strtolower((string) ($fieldDef['type'] ?? 'string'));
                    if ($type === 'file') {
                        $fileId = $blockData[$fieldKey . '_file_id'] ?? null;
                        if (is_numeric($fileId)) {
                            $fileIds[] = (int) $fileId;
                        }
                        continue;
                    }
                    if ($type === 'media_reference') {
                        $reference = $blockData[$fieldKey] ?? [];
                        if (is_array($reference)) {
                            $fileId = $reference['file_id'] ?? null;
                            if (is_numeric($fileId)) {
                                $fileIds[] = (int) $fileId;
                            }
                        }
                        continue;
                    }
                    if ($type === 'repeater' && is_array($blockData[$fieldKey] ?? null)) {
                        $itemFields = is_array($fieldDef['item_fields'] ?? null) ? (array) $fieldDef['item_fields'] : [];
                        foreach ($blockData[$fieldKey] as $item) {
                            if (is_array($item)) {
                                $fileIds = array_merge($fileIds, $this->collectBlockFileIds($item, $itemFields));
                            }
                        }
                    }
                }

                return array_values(array_unique($fileIds));
            }

            public function resolveMany(array $fileIds, string $context = 'public'): array
            {
                $map = [];
                foreach ($fileIds as $fileId) {
                    $map[(int) $fileId] = match ((int) $fileId) {
                        500 => 'http://localhost:8180/uploads/posts/feature_lg.png',
                        501 => 'http://localhost:8180/uploads/posts/gallery.png',
                        502 => 'http://localhost:8180/uploads/posts/gallery-poster.png',
                        default => 'http://localhost:8180/uploads/posts/' . (int) $fileId . '.png',
                    };
                }

                return $map;
            }

            public function resolveManyMeta(array $fileIds, string $context = 'public'): array
            {
                $urls = $this->resolveMany($fileIds, $context);
                $result = [];
                foreach ($urls as $id => $url) {
                    $result[$id] = [
                        'url' => $url,
                        'variants' => [
                            'lg' => ['url' => $url . '_lg.webp', 'width' => 1200],
                            'md' => ['url' => $url . '_md.webp', 'width' => 800],
                            'sm' => ['url' => $url . '_sm.webp', 'width' => 480],
                            'thumb' => ['url' => $url . '_thumb.webp', 'width' => 150],
                        ],
                    ];
                }
                return $result;
            }

            public function resolveUrlValue(int|string|null $fileId, ?string $currentUrl = null, string $context = 'public'): ?string
            {
                $fileId = is_numeric($fileId) ? (int) $fileId : null;
                return match ($fileId) {
                    500 => 'http://localhost:8180/uploads/posts/feature_lg.png',
                    501 => 'http://localhost:8180/uploads/posts/gallery.png',
                    502 => 'http://localhost:8180/uploads/posts/gallery-poster.png',
                    default => $currentUrl,
                };
            }

            public function resolveFileIdFromValue(int|string|null $fileId, ?string $url = null): ?int
            {
                return is_numeric($fileId) ? (int) $fileId : null;
            }

            public function resolve(int $fileId, string $context = 'public'): ?string
            {
                return $this->resolveUrlValue($fileId, null, $context);
            }
        };

        $this->serializer = new BlockInstanceSerializer($resolver);
        $this->seedDatabase();
    }

    private function seedDatabase(): void
    {
        $db = Database::connect();

        $db->query('SET FOREIGN_KEY_CHECKS = 0');
        $db->query("DELETE FROM `cms_file_translations`");
        $db->query("DELETE FROM `cms_block_instance_translations`");
        $db->query("DELETE FROM `cms_block_instances`");
        $db->query("DELETE FROM `cms_content_blocks`");
        $db->query("DELETE FROM `cms_languages`");
        $db->query('SET FOREIGN_KEY_CHECKS = 1');

        // Languages
        $db->table('cms_languages')->insert([
            'id'          => 1,
            'code'        => 'en',
            'name'        => 'English',
            'native_name' => 'English',
            'is_default'  => 1,
            'is_active'   => 1,
        ]);
        $db->table('cms_languages')->insert([
            'id'          => 2,
            'code'        => 'es',
            'name'        => 'Spanish',
            'native_name' => 'Español',
            'is_default'  => 0,
            'is_active'   => 1,
        ]);

        // Block types
        $db->table('cms_content_blocks')->insert([
            'id'                => 1,
            'block_key'         => 'rich_text',
            'name'              => 'Rich Text',
            'schema_definition' => '{}',
            'supports_pages'    => 1,
            'supports_entries'  => 1,
            'is_container'      => 0,
            'is_active'         => 1,
            'sort_order'        => 10,
        ]);
        $db->table('cms_content_blocks')->insert([
            'id'                => 2,
            'block_key'         => 'image',
            'name'              => 'Image',
            'schema_definition' => json_encode([
                'fields' => [
                    'alt' => ['type' => 'string', 'label' => 'Alt'],
                    'caption' => ['type' => 'string', 'label' => 'Caption'],
                ],
                'config_fields' => [
                    'image' => ['type' => 'media_reference', 'label' => 'Image', 'accept' => 'image'],
                    'aspect_ratio' => ['type' => 'select', 'label' => 'Aspect', 'options' => ['auto', '16/9'], 'default' => '16/9'],
                ],
            ]),
            'supports_pages'    => 1,
            'supports_entries'  => 1,
            'is_container'      => 0,
            'is_active'         => 1,
            'sort_order'        => 20,
        ]);
        $db->table('cms_content_blocks')->insert([
            'id'                => 3,
            'block_key'         => 'gallery',
            'name'              => 'Gallery',
            'schema_definition' => json_encode([
                'fields' => [
                    'items' => [
                        'type' => 'repeater',
                        'item_fields' => [
                            'image' => ['type' => 'media_reference', 'label' => 'Image', 'accept' => 'image'],
                            'caption' => ['type' => 'string', 'label' => 'Caption'],
                        ],
                    ],
                ],
            ]),
            'supports_pages'    => 1,
            'supports_entries'  => 1,
            'is_container'      => 0,
            'is_active'         => 1,
            'sort_order'        => 30,
        ]);
        $db->table('cms_content_blocks')->insert([
            'id'                => 4,
            'block_key'         => 'hero_slider',
            'name'              => 'Hero Slider',
            'schema_definition' => '{}',
            'supports_pages'    => 1,
            'supports_entries'  => 0,
            'is_container'      => 1,
            'is_active'         => 1,
            'sort_order'        => 5,
        ]);
        $db->table('cms_content_blocks')->insert([
            'id'                => 6,
            'block_key'         => 'video_gallery',
            'name'              => 'Video Gallery',
            'schema_definition' => json_encode([
                'fields' => [
                    'videos' => [
                        'type' => 'repeater',
                        'item_fields' => [
                            'video_url' => ['type' => 'url', 'label' => 'Video URL'],
                            'title' => ['type' => 'string', 'label' => 'Title'],
                            'poster' => ['type' => 'media_reference', 'label' => 'Poster', 'accept' => 'image'],
                        ],
                    ],
                ],
            ]),
            'supports_pages'    => 1,
            'supports_entries'  => 0,
            'is_container'      => 0,
            'is_active'         => 1,
            'sort_order'        => 31,
        ]);
        $db->table('cms_content_blocks')->insert([
            'id'                => 5,
            'block_key'         => 'slide_banner',
            'name'              => 'Slide Banner',
            'schema_definition' => json_encode([
                'fields' => [
                    'heading'   => ['type' => 'string', 'label' => 'Heading'],
                    'subtitle'  => ['type' => 'string', 'label' => 'Subtitle'],
                    'cta_label' => ['type' => 'string', 'label' => 'CTA'],
                    'cta_url'   => ['type' => 'url', 'label' => 'URL'],
                ],
                'config_fields' => [
                    'image' => ['type' => 'media_reference', 'label' => 'Image', 'accept' => 'image'],
                    'text_color' => ['type' => 'color', 'label' => 'Color', 'default' => '#ffffff'],
                    'overlay_color' => ['type' => 'color', 'label' => 'Overlay', 'default' => 'rgba(15, 23, 42, 0.4)'],
                ],
            ]),
            'supports_pages'    => 1,
            'supports_entries'  => 0,
            'is_container'      => 0,
            'is_active'         => 1,
            'sort_order'        => 6,
        ]);

        // Block instances for page 10
        $db->table('cms_block_instances')->insert([
            'id'           => 100,
            'block_id'     => 1, // rich_text
            'owner_type'   => 'page',
            'owner_id'     => 10,
            'sort_order'   => 1,
            'is_active'    => 1,
            'block_config' => json_encode(['alignment' => 'left']),
        ]);
        $db->table('cms_block_instances')->insert([
            'id'           => 101,
            'block_id'     => 2, // image
            'owner_type'   => 'page',
            'owner_id'     => 10,
            'sort_order'   => 2,
            'is_active'    => 1,
            'block_config' => json_encode([
                'aspect_ratio' => '16/9',
                'image' => [
                    'source_kind' => 'hub_file',
                    'file_id' => 500,
                    'url' => 'http://localhost:8182/files/500/view',
                ],
            ]),
        ]);
        $db->table('cms_block_instances')->insert([
            'id'           => 102,
            'block_id'     => 3, // gallery
            'owner_type'   => 'page',
            'owner_id'     => 10,
            'sort_order'   => 3,
            'is_active'    => 1,
            'block_config' => json_encode([]),
        ]);
        $db->table('cms_block_instances')->insert([
            'id'           => 103,
            'block_id'     => 4, // hero_slider
            'owner_type'   => 'page',
            'owner_id'     => 10,
            'sort_order'   => 4,
            'is_active'    => 1,
            'block_config' => json_encode([
                'caption_position'  => 'below',
                'controls_position' => 'below',
                'overlay_opacity'   => '0',
            ]),
        ]);
        $db->table('cms_block_instances')->insert([
            'id'                 => 104,
            'block_id'           => 5, // slide_banner
            'owner_type'         => 'page',
            'owner_id'           => 10,
            'parent_instance_id' => 103,
            'sort_order'         => 1,
            'is_active'          => 1,
            'block_config'       => json_encode([
                'image' => [
                    'source_kind' => 'hub_file',
                    'file_id' => 500,
                    'url' => 'http://localhost:8182/files/500/view',
                ],
            ]),
        ]);

        // Block instance translations
        // 100 (rich_text) in English
        $db->table('cms_block_instance_translations')->insert([
            'instance_id'  => 100,
            'language_id'  => 1,
            'block_data'   => json_encode(['content' => 'Hello World']),
            'is_published' => 1,
        ]);
        // 100 (rich_text) in Spanish
        $db->table('cms_block_instance_translations')->insert([
            'instance_id'  => 100,
            'language_id'  => 2,
            'block_data'   => json_encode(['content' => 'Hola Mundo']),
            'is_published' => 1,
        ]);

        // 101 (image) in English only (test fallback) — uses canonical {field}_file_id convention
        $db->table('cms_block_instance_translations')->insert([
            'instance_id'  => 101,
            'language_id'  => 1,
            'block_data'   => json_encode([
                'alt' => 'English Alt Image',
                'caption' => 'English Caption Image',
            ]),
            'is_published' => 1,
        ]);
        // 104 (slide_banner) in English only - child block of hero slider
        $db->table('cms_block_instance_translations')->insert([
            'instance_id'  => 104,
            'language_id'  => 1,
            'block_data'   => json_encode([
                'heading' => 'Child Slide',
                'subtitle' => 'Nested hero slide',
                'cta_label' => 'Read more',
                'cta_url' => '/child',
            ]),
            'is_published' => 1,
        ]);

        // 102 (gallery) in English only, with nested media_reference field
        $db->table('cms_block_instance_translations')->insert([
            'instance_id'  => 102,
            'language_id'  => 1,
            'block_data'   => json_encode([
                'items' => [
                    [
                        'image' => [
                            'source_kind' => 'hub_file',
                            'file_id' => 501,
                            'url' => 'http://localhost:8182/files/501/view',
                        ],
                        'caption' => 'Gallery item',
                    ],
                ],
            ]),
            'is_published' => 1,
        ]);
        $db->table('cms_block_instances')->insert([
            'id'           => 105,
            'block_id'     => 6, // video_gallery
            'owner_type'   => 'page',
            'owner_id'     => 10,
            'sort_order'   => 5,
            'is_active'    => 1,
            'block_config' => json_encode([]),
        ]);
        $db->table('cms_block_instance_translations')->insert([
            'instance_id'  => 105,
            'language_id'  => 1,
            'block_data'   => json_encode([
                'videos' => [
                    [
                        'video_url' => 'https://youtu.be/example',
                        'title' => 'Nested Poster Video',
                        'poster' => [
                            'source_kind' => 'hub_file',
                            'file_id' => 502,
                            'url' => 'http://localhost:8182/files/502/view',
                        ],
                    ],
                ],
            ]),
            'is_published' => 1,
        ]);

        // File translations for file 500
        $db->table('cms_file_translations')->insert([
            'file_id'     => 500,
            'language_id' => 1,
            'alt_text'    => 'English Alt Text',
            'caption'     => 'English Caption',
            'title'       => 'Title',
            'credit'      => 'Credit',
            'description' => 'Desc',
        ]);
        $db->table('cms_file_translations')->insert([
            'file_id'     => 501,
            'language_id' => 1,
            'alt_text'    => 'Gallery Alt',
            'caption'     => 'Gallery Caption',
            'title'       => 'Gallery Title',
            'credit'      => 'Gallery Credit',
            'description' => 'Gallery Description',
        ]);
        $db->table('cms_file_translations')->insert([
            'file_id'     => 500,
            'language_id' => 2,
            'alt_text'    => 'Texto Alt Español',
            'caption'     => 'Subtítulo Español',
            'title'       => 'Título',
            'credit'      => 'Crédito',
            'description' => 'Descripción',
        ]);
    }

    public function testForContentResolvesLanguageCorrectly(): void
    {
        $blocks = $this->serializer->forContent('page', 10, 'es');

        $this->assertCount(5, $blocks);

        // First block: rich_text in Spanish
        $this->assertEquals(100, $blocks[0]['id']);
        $this->assertEquals('rich_text', $blocks[0]['block_key']);
        $this->assertEquals('left', $blocks[0]['block_config']['alignment']);
        $this->assertEquals('Hola Mundo', $blocks[0]['block_data']['content']);
        $this->assertFalse($blocks[0]['is_fallback']);

        // Second block: image (English fallback used, as Spanish translation for instance is missing)
        $this->assertEquals(101, $blocks[1]['id']);
        $this->assertEquals('image', $blocks[1]['block_key']);
        $this->assertEquals('16/9', $blocks[1]['block_config']['aspect_ratio']);
        $this->assertSame('hub_file', $blocks[1]['block_config']['image']['source_kind']);
        $this->assertSame(500, $blocks[1]['block_config']['image']['file_id']);
        $this->assertSame('http://localhost:8180/uploads/posts/feature_lg.png', $blocks[1]['block_config']['image']['url']);
        $this->assertTrue($blocks[1]['is_fallback']);

        // Alt/caption now live in the block translation payload, not the file metadata.
        $this->assertEquals('English Alt Image', $blocks[1]['block_data']['alt']);
        $this->assertEquals('English Caption Image', $blocks[1]['block_data']['caption']);

        // Third block: gallery repeater resolves nested media_reference items too
        $this->assertEquals(102, $blocks[2]['id']);
        $this->assertEquals('gallery', $blocks[2]['block_key']);
        $this->assertSame('hub_file', $blocks[2]['block_data']['items'][0]['image']['source_kind']);
        $this->assertSame(501, $blocks[2]['block_data']['items'][0]['image']['file_id']);
        $this->assertSame('http://localhost:8180/uploads/posts/gallery.png', $blocks[2]['block_data']['items'][0]['image']['url']);

        // Fourth block: hero slider keeps the presentation config as stored
        $this->assertEquals(103, $blocks[3]['id']);
        $this->assertEquals('hero_slider', $blocks[3]['block_key']);
        $this->assertSame('below', $blocks[3]['block_config']['caption_position']);
        $this->assertSame('below', $blocks[3]['block_config']['controls_position']);
        $this->assertCount(1, $blocks[3]['children']);
        $this->assertEquals(104, $blocks[3]['children'][0]['id']);
        $this->assertEquals('slide_banner', $blocks[3]['children'][0]['block_key']);
        $this->assertSame('#ffffff', $blocks[3]['children'][0]['block_config']['text_color']);
        $this->assertSame('rgba(15, 23, 42, 0.4)', $blocks[3]['children'][0]['block_config']['overlay_color']);
        $this->assertSame(500, $blocks[3]['children'][0]['block_config']['image']['file_id']);
        $this->assertSame('http://localhost:8180/uploads/posts/feature_lg.png', $blocks[3]['children'][0]['block_config']['image']['url']);

        // Fifth block: repeater media_reference items keep the canonical nested shape
        $this->assertEquals(105, $blocks[4]['id']);
        $this->assertEquals('video_gallery', $blocks[4]['block_key']);
        $this->assertSame('hub_file', $blocks[4]['block_data']['videos'][0]['poster']['source_kind']);
        $this->assertSame(502, $blocks[4]['block_data']['videos'][0]['poster']['file_id']);
        $this->assertSame('http://localhost:8180/uploads/posts/gallery-poster.png', $blocks[4]['block_data']['videos'][0]['poster']['url']);
    }

    public function testForContentRetrievesDefaultWhenNoSpanishFileTranslation(): void
    {
        $blocks = $this->serializer->forContent('page', 10, 'en');

        $this->assertCount(5, $blocks);
        $this->assertEquals('Hello World', $blocks[0]['block_data']['content']);
        $this->assertEquals('English Alt Image', $blocks[1]['block_data']['alt']);
    }

    public function testForOwnersBatchGroupsTopLevelBlocksByOwner(): void
    {
        $db = Database::connect();
        $db->table('cms_block_instances')->insert([
            'id' => 106,
            'block_id' => 1,
            'owner_type' => 'page',
            'owner_id' => 11,
            'sort_order' => 1,
            'is_active' => 1,
            'block_config' => '{}',
        ]);
        $db->table('cms_block_instance_translations')->insert([
            'instance_id' => 106,
            'language_id' => 2,
            'block_data' => json_encode(['content' => 'Segundo dueño']),
            'is_published' => 1,
        ]);

        $blocksByOwner = $this->serializer->forOwnersBatch('page', [10, 11, 12], 'es');

        $this->assertCount(5, $blocksByOwner[10]);
        $this->assertSame('Segundo dueño', $blocksByOwner[11][0]['block_data']['content']);
        $this->assertSame([], $blocksByOwner[12]);
    }

    public function testMediaUrlsAreResolvedInOneHubBatchWithoutPerFieldRequests(): void
    {
        $hubClient = $this->createMock(HubClient::class);
        $hubClient->expects($this->once())
            ->method('resolvePublicFileMeta')
            ->with([500, 501, 502])
            ->willReturn([
                500 => ['url' => 'https://cdn.test/500.jpg'],
                501 => ['url' => 'https://cdn.test/501.jpg'],
                502 => ['url' => 'https://cdn.test/502.jpg'],
            ]);

        $blocks = (new BlockInstanceSerializer(new FileUrlResolver($hubClient)))
            ->forContent('page', 10, 'es');

        $this->assertSame('https://cdn.test/500.jpg', $blocks[1]['block_config']['image']['url']);
        $this->assertSame('https://cdn.test/501.jpg', $blocks[2]['block_data']['items'][0]['image']['url']);
        $this->assertSame('https://cdn.test/502.jpg', $blocks[4]['block_data']['videos'][0]['poster']['url']);
    }
}
