<?php

declare(strict_types=1);

namespace Tests\Unit\DTO\Request\Cms;

use App\DTO\Request\Cms\CollectionCreateRequestDTO;
use App\DTO\Request\Cms\CollectionUpdateRequestDTO;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Database;

/**
 * @internal
 */
final class CollectionRequestDTOTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBlockTypes();
    }

    private function seedBlockTypes(): void
    {
        $db = Database::connect();
        $db->query('SET FOREIGN_KEY_CHECKS = 0');
        $db->table('cms_content_blocks')->truncate();
        $db->query('SET FOREIGN_KEY_CHECKS = 1');

        foreach ([
            ['id' => 1, 'block_key' => 'rich_text', 'name' => 'Rich Text', 'sort_order' => 1],
            ['id' => 2, 'block_key' => 'image', 'name' => 'Image', 'sort_order' => 2],
        ] as $row) {
            $db->table('cms_content_blocks')->insert([
                'id' => $row['id'],
                'block_key' => $row['block_key'],
                'name' => $row['name'],
                'description' => null,
                'category' => 'content',
                'icon' => 'layout-template',
                'schema_definition' => '{}',
                'supports_pages' => 1,
                'supports_entries' => 1,
                'is_container' => 0,
                'is_active' => 1,
                'sort_order' => $row['sort_order'],
            ]);
        }
    }

    public function testCreateAndUpdateDtosSerializeTheSameCanonicalBlockTemplate(): void
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

        $create = $this->hydrateDto(CollectionCreateRequestDTO::class, [
            'collection_key' => 'news',
            'is_active' => 1,
            'requires_approval' => 0,
            'enables_categories' => 1,
            'enables_tags' => 1,
            'default_sitemap_priority' => '0.5',
            'default_changefreq' => 'weekly',
            'sort_order' => 4,
            'block_template' => $rawTemplate,
            'translations' => [],
        ]);

        $update = $this->hydrateDto(CollectionUpdateRequestDTO::class, [
            'block_template' => $rawTemplate,
        ]);

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

        $this->assertSame($canonical, $create->toArray()['block_template']);
        $this->assertSame($canonical, $update->toArray()['block_template']);
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
