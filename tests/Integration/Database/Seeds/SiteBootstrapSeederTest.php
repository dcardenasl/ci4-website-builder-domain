<?php

declare(strict_types=1);

namespace Tests\Integration\Database\Seeds;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Verifies the bootstrap seeds the contact form contract.
 *
 * @internal
 */
final class SiteBootstrapSeederTest extends CIUnitTestCase
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
            'cms_menu_item_translations',
            'cms_menu_items',
            'cms_menus',
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
            'cms_setting_translations',
            'cms_settings',
            'cms_languages'
        ];
        foreach ($tables as $table) {
            $this->db->query("DELETE FROM `{$table}`");
        }
        $this->db->enableForeignKeyChecks();
    }

    public function testBootstrapSeedsContactFormAndConnectsContactBlock(): void
    {
        $seeder = \Config\Database::seeder();
        $seeder->call(\App\Database\Seeds\SiteBootstrapSeeder::class);

        $form = $this->db->table('cms_forms')
            ->where('form_key', 'contact')
            ->get()
            ->getRowArray();

        $this->assertNotNull($form);
        $this->assertSame('contact', $form['form_key']);
        $this->assertSame('1', (string) $form['is_active']);
        $this->assertSame('email', $form['autoreply_email_field']);

        $formId = (int) $form['id'];
        $fields = $this->db->table('cms_form_fields')
            ->where('form_id', $formId)
            ->orderBy('display_order', 'ASC')
            ->get()
            ->getResultArray();

        $this->assertCount(3, $fields);
        $this->assertSame('name', $fields[0]['field_key']);
        $this->assertSame('email', $fields[1]['field_key']);
        $this->assertSame('message', $fields[2]['field_key']);

        $contactPage = $this->db->table('cms_pages')
            ->where('page_type', 'contact')
            ->get()
            ->getRowArray();
        $this->assertNotNull($contactPage);

        $contactBlockType = $this->db->table('cms_content_blocks')
            ->where('block_key', 'form_embed')
            ->get()
            ->getRowArray();
        $this->assertNotNull($contactBlockType);

        $collectionGridType = $this->db->table('cms_content_blocks')
            ->where('block_key', 'collection_grid')
            ->get()
            ->getRowArray();
        $this->assertNotNull($collectionGridType);

        $contactBlock = $this->db->table('cms_block_instances')
            ->where('block_id', (int) $contactBlockType['id'])
            ->where('owner_type', 'page')
            ->where('owner_id', (int) $contactPage['id'])
            ->get()
            ->getRowArray();

        $this->assertNotNull($contactBlock);
        $config = json_decode((string) $contactBlock['block_config'], true);
        $this->assertIsArray($config);
        $this->assertSame('contact', $config['form_key'] ?? null);
        $this->assertArrayNotHasKey('show_info_boxes', $config);

        $contactTranslation = $this->db->table('cms_block_instance_translations')
            ->where('instance_id', (int) $contactBlock['id'])
            ->get()
            ->getResultArray();

        $this->assertNotEmpty($contactTranslation);

        $newsCollection = $this->db->table('cms_collections')
            ->where('collection_key', 'noticias')
            ->get()
            ->getRowArray();
        $this->assertNotNull($newsCollection);
        $this->assertSame('news', $newsCollection['collection_type']);
        $this->assertNotEmpty($newsCollection['block_template']);
        $this->assertNotEmpty($newsCollection['wizard_config']);

        $aboutPage = $this->db->table('cms_pages')
            ->select('cms_pages.id')
            ->join('cms_page_translations', 'cms_page_translations.page_id = cms_pages.id')
            ->whereIn('cms_page_translations.slug', ['nosotros', 'about'])
            ->get()
            ->getRowArray();
        $this->assertNotNull($aboutPage);

        $aboutBlocks = $this->db->table('cms_block_instances')
            ->where('owner_type', 'page')
            ->where('owner_id', (int) $aboutPage['id'])
            ->orderBy('sort_order', 'ASC')
            ->get()
            ->getResultArray();
        $this->assertNotEmpty($aboutBlocks);
        $this->assertSame('page_header', $this->blockKeyForInstance((int) $aboutBlocks[0]['block_id']));

        $historyPage = $this->db->table('cms_pages')
            ->select('cms_pages.id')
            ->join('cms_page_translations', 'cms_page_translations.page_id = cms_pages.id')
            ->whereIn('cms_page_translations.slug', ['historia', 'history'])
            ->get()
            ->getRowArray();
        $this->assertNotNull($historyPage);

        $portfolioCollection = $this->db->table('cms_collections')
            ->where('collection_key', 'portafolio')
            ->get()
            ->getRowArray();
        $this->assertNotNull($portfolioCollection);

        $portfolioPage = $this->db->table('cms_pages')
            ->where('page_type', 'collection_index')
            ->where('collection_id', (int) $portfolioCollection['id'])
            ->get()
            ->getRowArray();
        $this->assertNotNull($portfolioPage);
        $this->assertNotEmpty($portfolioPage['collection_id'] ?? null);

        $portfolioBlocks = $this->db->table('cms_block_instances')
           ->select('cms_content_blocks.block_key')
           ->join('cms_content_blocks', 'cms_content_blocks.id = cms_block_instances.block_id')
           ->where('owner_type', 'page')
           ->where('owner_id', (int) $portfolioPage['id'])
           ->orderBy('cms_block_instances.sort_order', 'ASC')
           ->get()
           ->getResultArray();

        $portfolioBlockKeys = array_map(
            static fn (array $row): string => (string) ($row['block_key'] ?? ''),
            $portfolioBlocks
        );

        $this->assertContains('page_header', $portfolioBlockKeys);
        $this->assertContains('rich_text', $portfolioBlockKeys);
        $this->assertContains('collection_listing', $portfolioBlockKeys);
        $this->assertContains('tabs', $portfolioBlockKeys);
    }

    public function testBootstrapSeederIsIdempotent(): void
    {
        $seeder = \Config\Database::seeder();
        $seeder->call(\App\Database\Seeds\SiteBootstrapSeeder::class);

        $countsBefore = [
            'pages' => $this->db->table('cms_pages')->countAllResults(),
            'collections' => $this->db->table('cms_collections')->countAllResults(),
            'blocks' => $this->db->table('cms_block_instances')->countAllResults(),
            'file_references' => $this->db->table('cms_file_references')->countAllResults(),
        ];

        $seeder->call(\App\Database\Seeds\SiteBootstrapSeeder::class);

        $countsAfter = [
            'pages' => $this->db->table('cms_pages')->countAllResults(),
            'collections' => $this->db->table('cms_collections')->countAllResults(),
            'blocks' => $this->db->table('cms_block_instances')->countAllResults(),
            'file_references' => $this->db->table('cms_file_references')->countAllResults(),
        ];

        $this->assertSame($countsBefore, $countsAfter);
    }

    private function blockKeyForInstance(int $blockId): string
    {
        $row = $this->db->table('cms_content_blocks')
            ->where('id', $blockId)
            ->get()
            ->getRowArray();

        return (string) ($row['block_key'] ?? '');
    }
}
