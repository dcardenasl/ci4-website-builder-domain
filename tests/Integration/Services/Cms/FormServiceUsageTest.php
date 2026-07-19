<?php

declare(strict_types=1);

namespace Tests\Integration\Services\Cms;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Database;
use Config\Services;
use dcardenasl\Ci4ApiCore\Exceptions\ConflictException;
use Tests\Support\Fixtures\FixtureValueFactory;

/**
 * @internal
 */
final class FormServiceUsageTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    protected function setUp(): void
    {
        parent::setUp();

        $this->insertFixtureLanguages();
    }

    protected function tearDown(): void
    {
        Services::reset();
        parent::tearDown();
    }

    public function testGetUsagesReturnsLinkedPageAndEntryBlocks(): void
    {
        $db = Database::connect();
        $this->resetTables();

        $languages = $db->table('cms_languages')->orderBy('sort_order', 'ASC')->get()->getResultArray();
        $languageIdEs = (int) $languages[0]['id'];
        $languageIdEn = (int) $languages[1]['id'];

        $db->table('cms_forms')->insert([
            'id' => 77,
            'form_key' => 'gdpr_rights',
            'is_active' => 1,
            'has_captcha' => 0,
            'notify_email' => null,
            'autoreply_enabled' => 0,
            'autoreply_email_field' => null,
        ]);

        $db->table('cms_pages')->insert([
            'id' => 11,
            'page_type' => 'privacy',
            'status' => 'published',
            'sort_order' => 0,
            'sitemap_priority' => 0.6,
            'sitemap_changefreq' => 'monthly',
            'is_in_sitemap' => 1,
        ]);
        $db->table('cms_page_translations')->insert([
            'page_id' => 11,
            'language_id' => $languageIdEs,
            'slug' => 'derechos-datos',
            'title' => 'Derechos de Datos',
        ]);
        $db->table('cms_page_translations')->insert([
            'page_id' => 11,
            'language_id' => $languageIdEn,
            'slug' => 'data-rights',
            'title' => 'Data Rights',
        ]);

        $db->table('cms_collections')->insert([
            'id' => 4,
            'collection_key' => 'portfolio',
            'is_active' => 1,
            'requires_approval' => 0,
            'enables_categories' => 0,
            'enables_tags' => 0,
            'default_sitemap_priority' => 0.6,
            'default_changefreq' => 'monthly',
            'sort_order' => 0,
        ]);
        $db->table('cms_entries')->insert([
            'id' => 31,
            'collection_id' => 4,
            'author_id' => null,
            'workflow_status' => 'published',
            'published_at' => '2026-07-17 00:00:00',
            'scheduled_at' => null,
            'is_featured' => 0,
            'view_count' => 0,
            'sort_order' => 0,
            'sitemap_priority' => 0.6,
            'sitemap_changefreq' => 'monthly',
            'is_in_sitemap' => 1,
            'deleted_at' => null,
        ]);
        $db->table('cms_entry_translations')->insert([
            'entry_id' => 31,
            'language_id' => $languageIdEs,
            'slug' => 'mi-entrada',
            'title' => 'Mi Entrada',
        ]);

        $db->table('cms_content_blocks')->insert([
            'id' => 20,
            'block_key' => 'form_embed',
            'name' => 'Formulario Embebido',
            'description' => null,
            'category' => 'general',
            'icon' => null,
            'schema_definition' => json_encode(['fields' => []], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'supports_pages' => 1,
            'supports_entries' => 1,
            'is_container' => 0,
            'is_active' => 1,
            'sort_order' => 0,
        ]);

        $db->table('cms_block_instances')->insert([
            'id' => 101,
            'block_id' => 20,
            'owner_type' => 'page',
            'owner_id' => 11,
            'parent_instance_id' => null,
            'sort_order' => 0,
            'column_index' => null,
            'is_active' => 1,
            'block_config' => json_encode(['form_key' => 'gdpr_rights'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
        $db->table('cms_block_instances')->insert([
            'id' => 102,
            'block_id' => 20,
            'owner_type' => 'entry',
            'owner_id' => 31,
            'parent_instance_id' => null,
            'sort_order' => 0,
            'column_index' => null,
            'is_active' => 1,
            'block_config' => json_encode(['form_key' => 'gdpr_rights'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        $service = Services::formService(false);

        $usages = $service->getUsages(77, (string) $languages[0]['code']);

        $this->assertSame([
            [
                'resource' => 'block_instances',
                'resource_id' => 101,
                'role' => 'page',
                'label' => 'Derechos de Datos',
                'context' => [
                    'owner_type' => 'page',
                    'owner_id' => 11,
                    'block_key' => 'form_embed',
                    'block_name' => 'Formulario Embebido',
                ],
            ],
            [
                'resource' => 'block_instances',
                'resource_id' => 102,
                'role' => 'entry',
                'label' => 'Mi Entrada',
                'context' => [
                    'owner_type' => 'entry',
                    'owner_id' => 31,
                    'block_key' => 'form_embed',
                    'block_name' => 'Formulario Embebido',
                ],
            ],
        ], $usages);

        $dto = $service->get(77, (string) $languages[0]['code'])->toArray();
        $this->assertCount(2, $dto['usages']);
    }

    public function testDeleteThrowsConflictWhenFormIsLinked(): void
    {
        $db = Database::connect();
        $this->resetTables();

        $languageIdEs = (int) ($db->table('cms_languages')->orderBy('sort_order', 'ASC')->get()->getRowArray()['id'] ?? 0);

        $db->table('cms_forms')->insert([
            'id' => 78,
            'form_key' => 'contact',
            'is_active' => 1,
            'has_captcha' => 0,
            'notify_email' => null,
            'autoreply_enabled' => 0,
            'autoreply_email_field' => null,
        ]);

        $db->table('cms_pages')->insert([
            'id' => 12,
            'page_type' => 'contact',
            'status' => 'published',
            'sort_order' => 0,
            'sitemap_priority' => 0.6,
            'sitemap_changefreq' => 'monthly',
            'is_in_sitemap' => 1,
        ]);
        $db->table('cms_page_translations')->insert([
            'page_id' => 12,
            'language_id' => $languageIdEs,
            'slug' => 'contacto',
            'title' => 'Contacto',
        ]);

        $db->table('cms_content_blocks')->insert([
            'id' => 21,
            'block_key' => 'form_embed',
            'name' => 'Formulario Embebido',
            'description' => null,
            'category' => 'general',
            'icon' => null,
            'schema_definition' => json_encode(['fields' => []], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'supports_pages' => 1,
            'supports_entries' => 1,
            'is_container' => 0,
            'is_active' => 1,
            'sort_order' => 0,
        ]);
        $db->table('cms_block_instances')->insert([
            'id' => 103,
            'block_id' => 21,
            'owner_type' => 'page',
            'owner_id' => 12,
            'parent_instance_id' => null,
            'sort_order' => 0,
            'column_index' => null,
            'is_active' => 1,
            'block_config' => json_encode(['form_key' => 'contact'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        $service = Services::formService(false);

        $expectedUsage = sprintf(
            '%s "Contacto" (id 12, %s %s #103)',
            lang('Forms.usage_page'),
            lang('Forms.usage_instance'),
            'Formulario Embebido'
        );

        try {
            $service->delete(78);
            $this->fail('Expected ConflictException was not thrown.');
        } catch (ConflictException $exception) {
            $this->assertSame(lang('Forms.in_use', ['1', $expectedUsage]), $exception->getMessage());
        }
    }

    private function resetTables(): void
    {
        $db = Database::connect();
        $db->query('SET FOREIGN_KEY_CHECKS = 0');
        $db->query('DELETE FROM `cms_block_instance_translations`');
        $db->query('DELETE FROM `cms_block_instances`');
        $db->query('DELETE FROM `cms_content_blocks`');
        $db->query('DELETE FROM `cms_form_fields`');
        $db->query('DELETE FROM `cms_form_field_translations`');
        $db->query('DELETE FROM `cms_form_submissions`');
        $db->query('DELETE FROM `cms_form_translations`');
        $db->query('DELETE FROM `cms_forms`');
        $db->query('DELETE FROM `cms_entry_translations`');
        $db->query('DELETE FROM `cms_entries`');
        $db->query('DELETE FROM `cms_page_translations`');
        $db->query('DELETE FROM `cms_pages`');
        $db->query('DELETE FROM `cms_collection_translations`');
        $db->query('DELETE FROM `cms_collections`');
        $db->query('DELETE FROM `cms_languages`');
        $db->query('SET FOREIGN_KEY_CHECKS = 1');

        $this->insertFixtureLanguages();
    }

    private function insertFixtureLanguages(): void
    {
        $db = Database::connect();
        if ((int) $db->table('cms_languages')->countAllResults() > 0) {
            return;
        }

        $values = new FixtureValueFactory(self::class);
        foreach (range(0, 2) as $position) {
            $code = $values->locale($position);
            $db->table('cms_languages')->insert([
                'code' => $code,
                'name' => 'Fixture Language ' . ($position + 1),
                'native_name' => 'Fixture Language ' . ($position + 1),
                'is_default' => $position === 0 ? 1 : 0,
                'is_active' => 1,
                'sort_order' => $position,
            ]);
        }
    }
}
