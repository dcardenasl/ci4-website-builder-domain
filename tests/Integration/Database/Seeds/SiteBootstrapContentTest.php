<?php

declare(strict_types=1);

namespace Tests\Integration\Database\Seeds;

use App\Database\Seeds\SiteBootstrapSeeder;
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
        $this->db->table('cms_block_instance_translations')->truncate();
        $this->db->table('cms_block_instances')->truncate();
        $this->db->table('cms_content_blocks')->truncate();
        $this->db->table('cms_form_field_translations')->truncate();
        $this->db->table('cms_form_fields')->truncate();
        $this->db->table('cms_form_translations')->truncate();
        $this->db->table('cms_forms')->truncate();
        $this->db->table('cms_entry_categories')->truncate();
        $this->db->table('cms_entry_tags')->truncate();
        $this->db->table('cms_entry_translations')->truncate();
        $this->db->table('cms_entries')->truncate();
        $this->db->table('cms_category_translations')->truncate();
        $this->db->table('cms_categories')->truncate();
        $this->db->table('cms_tag_translations')->truncate();
        $this->db->table('cms_tags')->truncate();
        $this->db->table('cms_menu_item_translations')->truncate();
        $this->db->table('cms_menu_items')->truncate();
        $this->db->table('cms_menus')->truncate();
        $this->db->table('cms_page_translations')->truncate();
        $this->db->table('cms_pages')->truncate();
        $this->db->table('cms_collection_translations')->truncate();
        $this->db->table('cms_collections')->truncate();
        $this->db->table('cms_setting_translations')->truncate();
        $this->db->table('cms_settings')->truncate();
        $this->db->table('cms_languages')->truncate();
        $this->db->enableForeignKeyChecks();
    }

    public function testBootstrapSeedsCoreDemoPagesMenusAndBlocks(): void
    {
        $seeder = new SiteBootstrapSeeder(config('Database'));
        $seeder->run();

        $pages = $this->db->table('cms_pages')->whereIn('page_type', ['home', 'contact', 'about', 'history', 'events'])->get()->getResultArray();
        $this->assertCount(5, $pages);

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
            ->where('block_key', 'contact_form')
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
