<?php

declare(strict_types=1);

namespace Tests\Integration\Services\Cms;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Services;
use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;
use dcardenasl\Ci4ApiCore\Exceptions\ValidationException;
use Tests\Support\Fixtures\FixtureValueFactory;

/**
 * @internal
 */
final class CollectionServicePresetTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = false;
    protected $namespace   = 'App';

    private int $languageId;

    private string $languageCode;

    protected function setUp(): void
    {
        parent::setUp();

        $db = $this->db;
        $this->languageCode = (new FixtureValueFactory(self::class))->locale(0);
        $existing = $db->table('cms_languages')->where('code', $this->languageCode)->get()->getRowArray();
        if ($existing === null) {
            $db->table('cms_languages')->insert([
                'code' => $this->languageCode,
                'name' => 'Fixture Language',
                'native_name' => 'Fixture Language',
                'is_default' => 1,
                'is_active' => 1,
                'sort_order' => 0,
            ]);
            $existing = $db->table('cms_languages')->where('code', $this->languageCode)->get()->getRowArray();
        }
        $this->languageId = (int) $existing['id'];
    }

    protected function tearDown(): void
    {
        Services::reset();
        parent::tearDown();
    }

    public function testStorePersistsExplicitPresetPayload(): void
    {
        $db = $this->db;
        $db->disableForeignKeyChecks();
        $db->query("DELETE FROM `cms_collection_translations`");
        $db->query("DELETE FROM `cms_collections`");
        $db->enableForeignKeyChecks();

        $service = Services::collectionService(false);
        $service->store($this->dto([
            'collection_type' => 'portfolio',
            'collection_key' => 'portfolio',
            'block_template' => json_encode([
                'version' => '1.0',
                'blocks' => [
                    ['block_key' => 'image', 'label' => 'Proyecto', 'help_text' => 'Imagen o captura del trabajo', 'required' => true, 'locked' => false, 'block_config_defaults' => new \stdClass(), 'sort_order' => 1],
                ],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'wizard_config' => json_encode([
                'type' => 'portfolio',
                'steps' => [
                    ['step_title' => 'Nombre', 'step_hint' => 'Identifica el proyecto', 'fields' => [['key' => 'title', 'label' => 'Nombre', 'type' => 'text', 'required' => true]]],
                ],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'is_active' => '1',
            'requires_approval' => '0',
            'enables_categories' => '0',
            'enables_tags' => '0',
            'default_sitemap_priority' => '0.6',
            'default_changefreq' => 'monthly',
            'sort_order' => 5,
            'translations' => [],
        ]));

        $row = $db->table('cms_collections')
            ->where('collection_key', 'portfolio')
            ->get()
            ->getRowArray();

        $this->assertNotNull($row);
        $this->assertSame('portfolio', $row['collection_type']);
        $this->assertNotEmpty($row['block_template']);
        $this->assertNotEmpty($row['wizard_config']);

        $template = json_decode((string) $row['block_template'], true);
        $wizard = json_decode((string) $row['wizard_config'], true);
        $this->assertSame('image', $template['blocks'][0]['block_key'] ?? null);
        $this->assertSame('portfolio', $wizard['type'] ?? null);
    }

    public function testStoreWithoutPresetMetadataLeavesColumnsNull(): void
    {
        $db = $this->db;
        $db->disableForeignKeyChecks();
        $db->query("DELETE FROM `cms_collection_translations`");
        $db->query("DELETE FROM `cms_collections`");
        $db->enableForeignKeyChecks();

        $service = Services::collectionService(false);
        $service->store($this->dto([
            'collection_type' => 'blog',
            'collection_key' => 'blog',
            'is_active' => '1',
            'requires_approval' => '0',
            'enables_categories' => '0',
            'enables_tags' => '0',
            'default_sitemap_priority' => '0.6',
            'default_changefreq' => 'monthly',
            'sort_order' => 5,
            'translations' => [],
        ]));

        $row = $db->table('cms_collections')
            ->where('collection_key', 'blog')
            ->get()
            ->getRowArray();

        $this->assertNotNull($row);
        $this->assertNull($row['block_template']);
        $this->assertNull($row['wizard_config']);
    }

    public function testStoreWithWizardTranslationsPersistsCollection(): void
    {
        $db = $this->db;
        $db->disableForeignKeyChecks();
        $db->query("DELETE FROM `cms_collection_translations`");
        $db->query("DELETE FROM `cms_collections`");
        $db->enableForeignKeyChecks();

        $service = Services::collectionService(false);

        try {
            $service->store($this->dto([
                'collection_type' => 'blog',
                'collection_key' => 'blog-qa-service',
                'block_template' => json_encode([
                    'version' => '1.0',
                    'blocks' => [
                        ['block_key' => 'rich_text', 'label' => 'Titular', 'help_text' => 'Bloque principal de la noticia', 'required' => true, 'locked' => true, 'block_config_defaults' => new \stdClass(), 'sort_order' => 1],
                    ],
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'wizard_config' => json_encode([
                    'type' => 'blog',
                    'steps' => [
                        ['step_title' => 'Título', 'step_hint' => 'Define el nombre de la entrada', 'fields' => [['key' => 'title', 'label' => 'Título', 'type' => 'text', 'required' => true]]],
                    ],
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'is_active' => '1',
                'requires_approval' => '0',
                'enables_categories' => '0',
                'enables_tags' => '0',
                'default_sitemap_priority' => '0.6',
                'default_changefreq' => 'monthly',
                'sort_order' => 5,
                'translations' => [
                    [
                        'language_id' => $this->languageId,
                        'slug' => 'blog-qa-service',
                        'name' => 'Blog QA Service',
                        'description' => '',
                    ],
                ],
            ]));
        } catch (ValidationException $e) {
            $this->fail($e->getMessage() . ' | ' . json_encode($e->getErrors(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        $row = $db->table('cms_collections')
            ->where('collection_key', 'blog-qa-service')
            ->get()
            ->getRowArray();

        $this->assertNotNull($row);
        $this->assertSame('blog', $row['collection_type']);
        $this->assertNotEmpty($row['block_template']);
        $this->assertNotEmpty($row['wizard_config']);
    }

    public function testStoreWithWizardMinimalPayloadPersistsCollection(): void
    {
        $db = $this->db;
        $db->disableForeignKeyChecks();
        $db->query("DELETE FROM `cms_collection_translations`");
        $db->query("DELETE FROM `cms_collections`");
        $db->enableForeignKeyChecks();

        $service = Services::collectionService(false);

        try {
            $service->store($this->dto([
                'collection_type' => 'blog',
                'collection_key' => 'blog-qa-minimal',
                'sort_order' => 0,
                'translations' => [
                    [
                        'language_id' => $this->languageId,
                        'slug' => 'blog-qa-minimal',
                        'name' => 'Blog QA Minimal',
                        'description' => '',
                    ],
                ],
            ]));
        } catch (ValidationException $e) {
            $this->fail($e->getMessage() . ' | ' . json_encode($e->getErrors(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        $row = $db->table('cms_collections')
            ->where('collection_key', 'blog-qa-minimal')
            ->get()
            ->getRowArray();

        $this->assertNotNull($row);
        $this->assertSame('blog', $row['collection_type']);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function dto(array $data): DataTransferObjectInterface
    {
        return new class ($data) implements DataTransferObjectInterface {
            public function __construct(private array $data)
            {
            }

            public function toArray(): array
            {
                return $this->data;
            }
        };
    }
}
