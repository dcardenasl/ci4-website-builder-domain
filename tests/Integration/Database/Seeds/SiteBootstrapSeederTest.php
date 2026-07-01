<?php

declare(strict_types=1);

namespace Tests\Integration\Database\Seeds;

use App\Database\Seeds\SiteBootstrapSeeder;
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
        $this->db->table('cms_block_instance_translations')->truncate();
        $this->db->table('cms_block_instances')->truncate();
        $this->db->table('cms_content_blocks')->truncate();
        $this->db->table('cms_form_field_translations')->truncate();
        $this->db->table('cms_form_fields')->truncate();
        $this->db->table('cms_form_translations')->truncate();
        $this->db->table('cms_forms')->truncate();
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

    public function testBootstrapSeedsContactFormAndConnectsContactBlock(): void
    {
        $seeder = new SiteBootstrapSeeder(config('Database'));
        $seeder->run();

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
            ->where('block_key', 'contact_form')
            ->get()
            ->getRowArray();
        $this->assertNotNull($contactBlockType);

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
        $this->assertArrayHasKey('show_info_boxes', $config);

        $contactTranslation = $this->db->table('cms_block_instance_translations')
            ->where('instance_id', (int) $contactBlock['id'])
            ->get()
            ->getResultArray();

        $this->assertNotEmpty($contactTranslation);
    }
}
