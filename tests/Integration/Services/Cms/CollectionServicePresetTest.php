<?php

declare(strict_types=1);

namespace Tests\Integration\Services\Cms;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Services;
use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;
use dcardenasl\Ci4ApiCore\Exceptions\ValidationException;

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

    protected function tearDown(): void
    {
        Services::reset();
        parent::tearDown();
    }

    public function testStoreBackfillsPresetFromCollectionType(): void
    {
        $db = $this->db;
        $db->disableForeignKeyChecks();
        $db->table('cms_collection_translations')->truncate();
        $db->table('cms_collections')->truncate();
        $db->enableForeignKeyChecks();

        $service = Services::collectionService(false);
        $service->store($this->dto([
            'collection_type' => 'portfolio',
            'collection_key' => 'portfolio',
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

    public function testStoreCanSkipPresetWhenRequested(): void
    {
        $db = $this->db;
        $db->disableForeignKeyChecks();
        $db->table('cms_collection_translations')->truncate();
        $db->table('cms_collections')->truncate();
        $db->enableForeignKeyChecks();

        $service = Services::collectionService(false);
        $service->store($this->dto([
            'collection_type' => 'blog',
            'collection_key' => 'blog',
            'use_preset' => false,
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
        $db->table('cms_collection_translations')->truncate();
        $db->table('cms_collections')->truncate();
        $db->enableForeignKeyChecks();

        $service = Services::collectionService(false);

        try {
            $service->store($this->dto([
                'collection_type' => 'blog',
                'collection_key' => 'blog-qa-service',
                'is_active' => '1',
                'requires_approval' => '0',
                'enables_categories' => '0',
                'enables_tags' => '0',
                'default_sitemap_priority' => '0.6',
                'default_changefreq' => 'monthly',
                'sort_order' => 5,
                'translations' => [
                    [
                        'language_id' => 1,
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
        $db->table('cms_collection_translations')->truncate();
        $db->table('cms_collections')->truncate();
        $db->enableForeignKeyChecks();

        $service = Services::collectionService(false);

        try {
            $service->store($this->dto([
                'collection_type' => 'blog',
                'collection_key' => 'blog-qa-minimal',
                'sort_order' => 0,
                'translations' => [
                    [
                        'language_id' => 1,
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
