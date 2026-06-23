<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Cms;

use App\Interfaces\Cms\TranslationAuditServiceInterface;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Services;

/**
 * @internal
 */
final class TranslationAuditServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    private int $langEsId;
    private int $langEnId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db->disableForeignKeyChecks();
        $this->db->table('cms_page_translations')->truncate();
        $this->db->table('cms_pages')->truncate();
        $this->db->table('cms_menu_item_translations')->truncate();
        $this->db->table('cms_menu_items')->truncate();
        $this->db->table('cms_setting_translations')->truncate();
        $this->db->table('cms_settings')->truncate();
        $this->db->table('cms_languages')->truncate();
        $this->db->enableForeignKeyChecks();

        // Seed languages
        $this->db->table('cms_languages')->insert([
            'code'       => 'es',
            'name'       => 'Spanish',
            'native_name' => 'Español',
            'is_default' => 1,
            'is_active'  => 1,
        ]);
        $this->langEsId = $this->db->insertID();

        $this->db->table('cms_languages')->insert([
            'code'       => 'en',
            'name'       => 'English',
            'native_name' => 'English',
            'is_default' => 0,
            'is_active'  => 1,
        ]);
        $this->langEnId = $this->db->insertID();
    }

    public function testServiceImplementsItsInterface(): void
    {
        $service = Services::translationAuditService(false);
        $this->assertInstanceOf(TranslationAuditServiceInterface::class, $service);
    }

    public function testGetOverallCompleteness(): void
    {
        // Add a page
        $this->db->table('cms_pages')->insert([
            'page_type' => 'generic',
            'status' => 'published',
            'sort_order' => 1,
        ]);
        $pageId = $this->db->insertID();

        // Translation for Spanish (complete)
        $this->db->table('cms_page_translations')->insert([
            'page_id' => $pageId,
            'language_id' => $this->langEsId,
            'slug' => 'inicio',
            'title' => 'Inicio',
        ]);

        // Translation for English (incomplete: missing slug)
        $this->db->table('cms_page_translations')->insert([
            'page_id' => $pageId,
            'language_id' => $this->langEnId,
            'slug' => '',
            'title' => 'Home',
        ]);

        $service = Services::translationAuditService(false);
        $stats = $service->getOverallCompleteness();

        $this->assertCount(2, $stats);

        $esStat = array_values(array_filter($stats, fn ($s) => $s['code'] === 'es'))[0];
        $enStat = array_values(array_filter($stats, fn ($s) => $s['code'] === 'en'))[0];

        // Spanish: 1 page complete / 1 page total = 100%
        $this->assertEquals(100, $esStat['percentage']);
        // English: 0 page complete / 1 page total = 0%
        $this->assertEquals(0, $enStat['percentage']);
    }

    public function testGetMissingTranslationsReport(): void
    {
        $this->db->table('cms_pages')->insert([
            'page_type' => 'generic',
            'status' => 'published',
            'sort_order' => 1,
        ]);
        $pageId = $this->db->insertID();

        // Complete ES
        $this->db->table('cms_page_translations')->insert([
            'page_id' => $pageId,
            'language_id' => $this->langEsId,
            'slug' => 'inicio',
            'title' => 'Inicio',
        ]);

        // Missing EN completely
        $service = Services::translationAuditService(false);
        $report = $service->getMissingTranslationsReport();

        $this->assertCount(1, $report);
        $this->assertEquals('page', $report[0]['resource']);
        $this->assertEquals($pageId, $report[0]['resource_id']);
        $this->assertEquals($this->langEnId, $report[0]['language_id']);
        $this->assertEquals('missing', $report[0]['status']);
    }

    public function testMissingSettingTranslationRowsAreIgnoredWhenBaseValueExists(): void
    {
        $this->db->table('cms_settings')->insert([
            'setting_key' => 'site_name',
            'setting_value' => 'Mi Sitio',
            'setting_type' => 'string',
            'setting_group' => 'identity',
            'is_translatable' => 1,
            'is_public' => 1,
            'is_active' => 1,
            'sort_order' => 10,
        ]);

        $service = Services::translationAuditService(false);
        $report = $service->getMissingTranslationsReport();

        $this->assertSame([], $report);
    }

    public function testAuditResource(): void
    {
        $this->db->table('cms_pages')->insert([
            'page_type' => 'generic',
            'status' => 'published',
            'sort_order' => 1,
        ]);
        $pageId = $this->db->insertID();

        // Incomplete ES (missing slug)
        $this->db->table('cms_page_translations')->insert([
            'page_id' => $pageId,
            'language_id' => $this->langEsId,
            'slug' => '',
            'title' => 'Inicio',
        ]);

        $service = Services::translationAuditService(false);
        $audit = $service->auditResource('page', $pageId);

        $this->assertEquals('incomplete', $audit['es']['status']);
        $this->assertEquals('missing', $audit['en']['status']);
    }
}
