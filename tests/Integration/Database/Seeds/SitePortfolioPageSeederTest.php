<?php

declare(strict_types=1);

namespace Tests\Integration\Database\Seeds;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Verifies the portfolio page seeder repairs legacy singleton rows in place.
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

    public function testSeederRepairsLegacyPortfolioPageWithoutDuplicatingIt(): void
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

        $legacyPageId = $this->db->table('cms_pages')->insert([
            'page_type'          => 'portfolio',
            'status'             => 'published',
            'published_at'       => date('Y-m-d H:i:s'),
            'scheduled_at'       => null,
            'sort_order'         => 40,
            'sitemap_priority'   => '0.8',
            'sitemap_changefreq' => 'weekly',
            'is_in_sitemap'      => 1,
            'deleted_at'         => null,
            'created_at'         => date('Y-m-d H:i:s'),
            'updated_at'         => date('Y-m-d H:i:s'),
        ]) ? (int) $this->db->insertID() : 0;

        $this->assertGreaterThan(0, $legacyPageId);

        $seeder->call(\App\Database\Seeds\SitePortfolioPageSeeder::class);

        $page = $this->db->table('cms_pages')
            ->where('id', $legacyPageId)
            ->get()
            ->getRowArray();

        $this->assertNotNull($page);
        $this->assertSame('collection_index', $page['page_type']);
        $this->assertSame((string) $collection['id'], (string) ($page['collection_id'] ?? ''));

        $pagesForCollection = $this->db->table('cms_pages')
            ->where('collection_id', (int) $collection['id'])
            ->where('page_type', 'collection_index')
            ->countAllResults();
        $this->assertSame(1, $pagesForCollection);
    }
}
