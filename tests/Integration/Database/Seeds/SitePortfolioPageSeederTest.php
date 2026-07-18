<?php

declare(strict_types=1);

namespace Tests\Integration\Database\Seeds;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Verifies canonical portfolio page creation and idempotency.
 *
 * @internal
 */
final class SitePortfolioPageSeederTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = false;
    protected $namespace   = 'App';

    protected function setUp(): void
    {
        parent::setUp();

        $this->db->disableForeignKeyChecks();
        $tables = [
            'cms_file_references',
            'cms_block_instance_translations',
            'cms_block_instances',
            'cms_content_blocks',
            'cms_entry_categories',
            'cms_entry_tags',
            'cms_entry_translations',
            'cms_entries',
            'cms_category_translations',
            'cms_categories',
            'cms_tag_translations',
            'cms_tags',
            'cms_page_translations',
            'cms_pages',
            'cms_collection_translations',
            'cms_collections',
            'cms_languages',
        ];

        foreach ($tables as $table) {
            $this->db->query("DELETE FROM `{$table}`");
        }
        $this->db->enableForeignKeyChecks();
    }

    public function testSeederCreatesOneCanonicalPortfolioPageAcrossRepeatedRuns(): void
    {
        $seeder = \Config\Database::seeder();
        $seeder->call(\App\Database\Seeds\CmsLanguageSeeder::class);
        $seeder->call(\App\Database\Seeds\CmsBlockTypeSeeder::class);
        $seeder->call(\App\Database\Seeds\PortfolioCollectionSeeder::class);

        $collection = $this->db->table('cms_collections')
            ->where('collection_key', 'portafolio')
            ->get()
            ->getRowArray();
        $this->assertNotNull($collection);

        $seeder->call(\App\Database\Seeds\SitePortfolioPageSeeder::class);
        $seeder->call(\App\Database\Seeds\SitePortfolioPageSeeder::class);

        $page = $this->db->table('cms_pages')
            ->where('page_type', 'collection_index')
            ->where('collection_id', (int) $collection['id'])
            ->get()
            ->getRowArray();

        $this->assertNotNull($page);
        $pageId = (int) $page['id'];
        $this->assertGreaterThan(0, $pageId);
        $this->assertSame('collection_index', $page['page_type']);
        $this->assertSame((string) $collection['id'], (string) ($page['collection_id'] ?? ''));

        $pagesForCollection = $this->db->table('cms_pages')
            ->where('collection_id', (int) $collection['id'])
            ->where('page_type', 'collection_index')
            ->countAllResults();
        $this->assertSame(1, $pagesForCollection);

        $imageBlock = $this->db->table('cms_block_instances')
            ->select('cms_block_instances.block_config, cms_block_instances.id')
            ->join('cms_content_blocks', 'cms_content_blocks.id = cms_block_instances.block_id')
            ->where('cms_block_instances.owner_type', 'page')
            ->where('cms_block_instances.owner_id', $pageId)
            ->where('cms_content_blocks.block_key', 'image')
            ->get()
            ->getRowArray();

        $this->assertNotNull($imageBlock);

        $imageConfig = json_decode((string) ($imageBlock['block_config'] ?? '{}'), true);
        $this->assertIsArray($imageConfig);
        $this->assertSame('external_url', $imageConfig['image']['source_kind'] ?? null);
        $this->assertSame('https://picsum.photos/id/355/1200/675', $imageConfig['image']['url'] ?? null);
    }
}
