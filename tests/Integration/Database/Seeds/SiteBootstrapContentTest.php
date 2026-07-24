<?php

declare(strict_types=1);

namespace Tests\Integration\Database\Seeds;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Verifies the bootstrap seeds the starter's demo content set.
 *
 * @internal
 */
final class SiteBootstrapContentTest extends CIUnitTestCase
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
            'cms_form_field_translations',
            'cms_form_fields',
            'cms_form_translations',
            'cms_forms',
            'cms_entry_categories',
            'cms_entry_tags',
            'cms_entry_translations',
            'cms_entries',
            'cms_category_translations',
            'cms_categories',
            'cms_tag_translations',
            'cms_tags',
            'cms_menu_item_translations',
            'cms_menu_items',
            'cms_menus',
            'cms_page_translations',
            'cms_pages',
            'cms_collection_translations',
            'cms_collections',
            'cms_setting_translations',
            'cms_settings',
            'cms_languages'
        ];
        foreach ($tables as $table) {
            $this->db->query("DELETE FROM `{$table}`");
        }
        $this->db->enableForeignKeyChecks();
    }

    public function testBootstrapSeedsCoreDemoPagesMenusAndBlocks(): void
    {
        $seeder = \Config\Database::seeder();
        $seeder->call(\App\Database\Seeds\SiteBootstrapSeeder::class);

        $pages = $this->db->table('cms_pages')->whereIn('page_type', ['home', 'contact', 'generic', 'collection_index'])->get()->getResultArray();
        $this->assertNotEmpty($pages);

        $pageTypes = array_map(static fn (array $row): string => (string) $row['page_type'], $pages);
        foreach (['home', 'contact', 'generic'] as $pageType) {
            $this->assertContains($pageType, $pageTypes, sprintf('Missing %s page in bootstrap.', $pageType));
        }

        $mediaPage = $this->db->table('cms_page_translations')
            ->whereIn('slug', ['multimedia', 'media'])
            ->get()->getRowArray();
        $this->assertNotNull($mediaPage, 'Missing media page in bootstrap.');

        $collectionIndexCount = count(array_filter($pageTypes, static fn (string $pageType): bool => $pageType === 'collection_index'));
        $this->assertGreaterThanOrEqual(2, $collectionIndexCount);

        $menu = $this->db->table('cms_menus')->whereIn('menu_key', ['main', 'footer'])->get()->getResultArray();
        $this->assertCount(2, $menu);

        $newsCollection = $this->db->table('cms_collections')
            ->where('collection_key', 'noticias')
            ->get()
            ->getRowArray();
        $this->assertNotNull($newsCollection);

        $homePage = $this->db->table('cms_pages')
            ->where('page_type', 'home')
            ->get()
            ->getRowArray();
        $homeBlocks = $this->db->table('cms_block_instances')
            ->where('owner_type', 'page')
            ->where('owner_id', (int) $homePage['id'])
            ->orderBy('sort_order', 'ASC')
            ->get()
            ->getResultArray();
        $this->assertGreaterThanOrEqual(3, count($homeBlocks));

        $heroBlock = $this->db->table('cms_content_blocks')
            ->where('block_key', 'hero_slider')
            ->get()
            ->getRowArray();
        $this->assertNotNull($heroBlock);

        $heroInstance = $this->db->table('cms_block_instances')
            ->where('owner_type', 'page')
            ->where('owner_id', (int) $homePage['id'])
            ->where('block_id', (int) $heroBlock['id'])
            ->get()
            ->getRowArray();
        $this->assertNotNull($heroInstance);

        $contactBlock = $this->db->table('cms_content_blocks')
            ->where('block_key', 'form_embed')
            ->get()
            ->getRowArray();
        $this->assertNotNull($contactBlock);

        $contactPage = $this->db->table('cms_pages')
            ->where('page_type', 'contact')
            ->get()
            ->getRowArray();
        $this->assertNotNull($contactPage);

        $contactInstance = $this->db->table('cms_block_instances')
            ->where('owner_type', 'page')
            ->where('owner_id', (int) $contactPage['id'])
            ->where('block_id', (int) $contactBlock['id'])
            ->get()
            ->getRowArray();
        $this->assertNotNull($contactInstance);

        $config = json_decode((string) $contactInstance['block_config'], true);
        $this->assertSame('contact', $config['form_key'] ?? null);
    }
}
