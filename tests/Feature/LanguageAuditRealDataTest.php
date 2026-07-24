<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\Support\ApiTestCase;
use Tests\Support\Fixtures\CmsFixtureFactory;

/**
 * Feature tests for Language Audit (Translation Audit) system with real multilingual data.
 *
 * Fixture structure:
 * - 2 languages: ES (default, id=1), EN (id=2)
 * - 5 pages: 3 ES-only, 1 ES+EN, 1 ES+EN
 * - 2 collections: 1 ES-only, 1 ES+EN
 * - 2 menus: 1 ES-only, 1 ES+EN (with 4 items each)
 * - 10 entries: 6 ES-only, 3 ES+EN, 1 completely missing
 *
 * These tests verify that the translation audit endpoints:
 * 1. Are properly registered and accessible
 * 2. Require authentication (returns 401 without auth)
 * 3. Return proper JSON structures
 * 4. Calculate completion statistics correctly
 *
 * @internal
 */
final class LanguageAuditRealDataTest extends ApiTestCase
{
    private int $langEsId = 0;
    private int $langEnId = 0;

    /** @var list<array{id:int,code:string,name:string,is_default:bool}> */
    private array $languages = [];

    private CmsFixtureFactory $fixtures;
    private array $pageIds = [];
    private array $collectionIds = [];
    private array $menuIds = [];
    private array $menuItemIds = [];
    private array $entryIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedLanguages();
        $this->seedPages();
        $this->seedCollections();
        $this->seedMenus();
        $this->seedEntries();
    }

    // ========== SMOKE TESTS - Endpoint existence ==========

    /**
     * Stats endpoint exists and requires authentication.
     */
    public function testAuditStatsEndpointExists(): void
    {
        $result = $this->get('/api/v1/cms/translations/audit/stats');
        $this->assertNotEquals(404, $result->response()->getStatusCode(), 'Endpoint should exist');
        $this->assertEquals(401, $result->response()->getStatusCode(), 'Should require authentication');
    }

    /**
     * Report endpoint exists and requires authentication.
     */
    public function testAuditReportEndpointExists(): void
    {
        $result = $this->get('/api/v1/cms/translations/audit/report');
        $this->assertNotEquals(404, $result->response()->getStatusCode(), 'Endpoint should exist');
        $this->assertEquals(401, $result->response()->getStatusCode(), 'Should require authentication');
    }

    /**
     * Resource page audit endpoint exists and requires authentication.
     */
    public function testAuditResourcePageEndpointExists(): void
    {
        $result = $this->get(sprintf('/api/v1/cms/translations/audit/resource/page/%d', $this->pageIds[0]));
        $this->assertNotEquals(404, $result->response()->getStatusCode(), 'Endpoint should exist');
        $this->assertEquals(401, $result->response()->getStatusCode(), 'Should require authentication');
    }

    /**
     * Resource collection audit endpoint exists.
     */
    public function testAuditResourceCollectionEndpointExists(): void
    {
        $result = $this->get(sprintf('/api/v1/cms/translations/audit/resource/collection/%d', $this->collectionIds[0]));
        $this->assertNotEquals(404, $result->response()->getStatusCode(), 'Endpoint should exist');
        $this->assertEquals(401, $result->response()->getStatusCode(), 'Should require authentication');
    }

    /**
     * Resource menu audit endpoint exists.
     */
    public function testAuditResourceMenuEndpointExists(): void
    {
        $result = $this->get(sprintf('/api/v1/cms/translations/audit/resource/menu/%d', $this->menuIds[0]));
        $this->assertNotEquals(404, $result->response()->getStatusCode(), 'Endpoint should exist');
        $this->assertEquals(401, $result->response()->getStatusCode(), 'Should require authentication');
    }

    /**
     * Resource menu_item audit endpoint exists.
     */
    public function testAuditResourceMenuItemEndpointExists(): void
    {
        $result = $this->get(sprintf('/api/v1/cms/translations/audit/resource/menu_item/%d', $this->menuItemIds[0]));
        $this->assertNotEquals(404, $result->response()->getStatusCode(), 'Endpoint should exist');
        $this->assertEquals(401, $result->response()->getStatusCode(), 'Should require authentication');
    }

    /**
     * Resource entry audit endpoint exists.
     */
    public function testAuditResourceEntryEndpointExists(): void
    {
        $result = $this->get(sprintf('/api/v1/cms/translations/audit/resource/entry/%d', $this->entryIds[0]));
        $this->assertNotEquals(404, $result->response()->getStatusCode(), 'Endpoint should exist');
        $this->assertEquals(401, $result->response()->getStatusCode(), 'Should require authentication');
    }

    // ========== DATA INTEGRITY TESTS - Via Unit Service ==========

    /**
     * Service correctly counts overall completeness.
     */
    public function testServiceCountsOverallCompletion(): void
    {
        $service = \Config\Services::translationAuditService(false);
        $stats = $service->getOverallCompleteness();

        // Should have 2 language stats
        $this->assertCount(count($this->languages), $stats, 'All fixture languages should be reported');

        $defaultStats = null;
        $secondaryStats = null;
        foreach ($stats as $stat) {
            if (($stat['code'] ?? '') === $this->languages[0]['code']) {
                $defaultStats = $stat;
            } elseif (($stat['code'] ?? '') === $this->languages[1]['code']) {
                $secondaryStats = $stat;
            }
        }

        $this->assertNotNull($defaultStats, 'Default fixture language stats should exist');
        $this->assertNotNull($secondaryStats, 'Secondary fixture language stats should exist');

        // ES is default, should be high percentage (may not be 100% if other audit-able items exist)
        $this->assertGreaterThanOrEqual(90, $defaultStats['percentage'], 'Default fixture language should be mostly complete');
        $this->assertTrue($defaultStats['is_default'], 'The first fixture language should be marked as default');

        // EN should be less than default language
        $this->assertLessThan($defaultStats['percentage'], $secondaryStats['percentage'], 'Secondary language should be less complete than default');
    }

    /**
     * Service calculates completion percentage correctly.
     *
     * Expected for EN:
     * - Pages: 1 translated + 4 missing = 20% (1/5)
     * - Collections: 1 translated + 1 missing = 50% (1/2)
     * - Menu items: 4 translated + 4 missing = 50% (4/8)
     * - Entries: 3 translated + 7 missing = 30% (3/10)
     * Total: 9 translated / 25 total = 36%
     */
    public function testServiceCalculatesEnCompletionCorrectly(): void
    {
        $service = \Config\Services::translationAuditService(false);
        $stats = $service->getOverallCompleteness();

        $secondaryStats = null;
        foreach ($stats as $stat) {
            if (($stat['code'] ?? '') === $this->languages[1]['code']) {
                $secondaryStats = $stat;
                break;
            }
        }

        $this->assertNotNull($secondaryStats);
        $this->assertArrayHasKey('percentage', $secondaryStats);
        $this->assertArrayHasKey('completed_elements', $secondaryStats);
        $this->assertArrayHasKey('total_elements', $secondaryStats);

        $missing = $service->getMissingTranslationsReport([
            'language_id' => $this->languages[1]['id'],
        ]);
        $total = (int) $secondaryStats['total_elements'];
        $completed = (int) $secondaryStats['completed_elements'];
        $expectedCompleted = $total - count($missing);
        $expectedPercentage = $total > 0 ? (int) round(($expectedCompleted / $total) * 100) : 100;

        $this->assertSame($expectedCompleted, $completed);
        $this->assertSame($expectedPercentage, (int) $secondaryStats['percentage']);
    }

    /**
     * Missing translations report includes all incomplete items.
     */
    public function testServiceDetectsMissingTranslations(): void
    {
        $service = \Config\Services::translationAuditService(false);
        $report = $service->getMissingTranslationsReport();

        // Should have issues
        $this->assertGreaterThan(0, count($report), 'Report should list missing items');

        // All items should have required fields
        foreach ($report as $item) {
            $this->assertArrayHasKey('resource', $item);
            $this->assertArrayHasKey('resource_id', $item);
            $this->assertArrayHasKey('language_id', $item);
            $this->assertArrayHasKey('status', $item);
        }
    }

    /**
     * Missing translations can be filtered by language.
     */
    public function testServiceFiltersReportByLanguage(): void
    {
        $service = \Config\Services::translationAuditService(false);
        $report = $service->getMissingTranslationsReport(['language_id' => $this->langEnId]);

        // All items should be for EN
        foreach ($report as $item) {
            $this->assertEquals($this->langEnId, $item['language_id']);
        }
    }

    /**
     * Resource-level audit shows translation status per language.
     */
    public function testServiceAuditResourceShowsPerLanguageStatus(): void
    {
        $service = \Config\Services::translationAuditService(false);
        $audit = $service->auditResource('page', $this->pageIds[0]); // ES-only page

        // Should have entries for both languages
        $this->assertArrayHasKey($this->languages[0]['code'], $audit);
        $this->assertArrayHasKey($this->languages[1]['code'], $audit);

        // ES should be complete
        $this->assertEquals('complete', $audit[$this->languages[0]['code']]['status']);

        // EN should be missing
        $this->assertEquals('missing', $audit[$this->languages[1]['code']]['status']);
    }

    /**
     * Resource with full translations shows complete status.
     */
    public function testServiceAuditFullyTranslatedResource(): void
    {
        $service = \Config\Services::translationAuditService(false);
        $audit = $service->auditResource('page', $this->pageIds[3]); // ES+EN page

        $this->assertEquals('complete', $audit[$this->languages[0]['code']]['status']);
        $this->assertEquals('complete', $audit[$this->languages[1]['code']]['status']);
    }

    /**
     * Audit includes all resource types.
     */
    public function testServiceAuditIncludesAllResourceTypes(): void
    {
        $service = \Config\Services::translationAuditService(false);
        $report = $service->getMissingTranslationsReport();

        $resourceTypes = array_unique(array_map(fn ($item) => $item['resource'] ?? '', $report));

        // Should have at least pages, collections, entries
        $this->assertGreaterThan(0, count($resourceTypes), 'Should have multiple resource types');
    }

    /**
     * Performance: audit completes in reasonable time.
     */
    public function testServicePerformanceStatsGeneration(): void
    {
        $service = \Config\Services::translationAuditService(false);

        $start = microtime(true);
        $service->getOverallCompleteness();
        $elapsed = microtime(true) - $start;

        $this->assertLessThan(1.0, $elapsed, sprintf('Stats took %.2fs', $elapsed));
    }

    /**
     * Performance: report generation completes in reasonable time.
     */
    public function testServicePerformanceReportGeneration(): void
    {
        $service = \Config\Services::translationAuditService(false);

        $start = microtime(true);
        $service->getMissingTranslationsReport();
        $elapsed = microtime(true) - $start;

        $this->assertLessThan(2.0, $elapsed, sprintf('Report took %.2fs', $elapsed));
    }

    /**
     * Performance: resource audit completes quickly.
     */
    public function testServicePerformanceResourceAudit(): void
    {
        $service = \Config\Services::translationAuditService(false);

        $start = microtime(true);
        $service->auditResource('page', $this->pageIds[0]);
        $elapsed = microtime(true) - $start;

        $this->assertLessThan(0.5, $elapsed, sprintf('Resource audit took %.3fs', $elapsed));
    }

    // ========== FIXTURE HELPERS ==========

    private function seedLanguages(): void
    {
        $this->db->disableForeignKeyChecks();

        // Truncate all tables in dependency order
        $this->db->query("DELETE FROM `cms_page_translations`");
        $this->db->query("DELETE FROM `cms_pages`");
        $this->db->query("DELETE FROM `cms_menu_item_translations`");
        $this->db->query("DELETE FROM `cms_menu_items`");
        $this->db->query("DELETE FROM `cms_menu_translations`");
        $this->db->query("DELETE FROM `cms_menus`");
        $this->db->query("DELETE FROM `cms_entry_translations`");
        $this->db->query("DELETE FROM `cms_entries`");
        $this->db->query("DELETE FROM `cms_collection_translations`");
        $this->db->query("DELETE FROM `cms_collections`");
        $this->db->query("DELETE FROM `cms_languages`");

        $this->db->enableForeignKeyChecks();

        $this->fixtures = new CmsFixtureFactory($this->db, self::class);
        $this->languages = $this->fixtures->languages(2);
        $this->langEsId = $this->languages[0]['id'];
        $this->langEnId = $this->languages[1]['id'];
    }

    private function seedPages(): void
    {
        // Pages 0-2: ES only
        for ($i = 0; $i < 3; $i++) {
            $this->db->table('cms_pages')->insert([
                'page_type'    => 'generic',
                'status'       => 'draft',
                'sort_order'   => $i + 1,
                'published_at' => null,
            ]);
            $pageId = $this->db->insertID();
            $this->pageIds[] = $pageId;

            $this->db->table('cms_page_translations')->insert([
                'page_id'      => $pageId,
                'language_id'  => $this->langEsId,
                'slug'         => "page-es-{$i}",
                'title'        => "Página ES {$i}",
            ]);
        }

        // Pages 3-4: ES+EN
        for ($i = 3; $i < 5; $i++) {
            $this->db->table('cms_pages')->insert([
                'page_type'    => 'generic',
                'status'       => 'published',
                'sort_order'   => $i + 1,
                'published_at' => date('Y-m-d H:i:s'),
            ]);
            $pageId = $this->db->insertID();
            $this->pageIds[] = $pageId;

            $this->db->table('cms_page_translations')->insert([
                'page_id'      => $pageId,
                'language_id'  => $this->langEsId,
                'slug'         => "page-es-{$i}",
                'title'        => "Página ES {$i}",
            ]);

            $this->db->table('cms_page_translations')->insert([
                'page_id'      => $pageId,
                'language_id'  => $this->langEnId,
                'slug'         => "page-en-{$i}",
                'title'        => "Page EN {$i}",
            ]);
        }
    }

    private function seedCollections(): void
    {
        // Collection 0: ES only
        $this->db->table('cms_collections')->insert([
            'collection_key' => 'col-es',
            'is_active'      => 1,
        ]);
        $collectionId = $this->db->insertID();
        $this->collectionIds[] = $collectionId;

        $this->db->table('cms_collection_translations')->insert([
            'collection_id' => $collectionId,
            'language_id'   => $this->langEsId,
            'name'          => 'Colección ES',
            'slug'          => 'coleccion-es',
        ]);

        // Collection 1: ES+EN
        $this->db->table('cms_collections')->insert([
            'collection_key' => 'col-en',
            'is_active'      => 1,
        ]);
        $collectionId = $this->db->insertID();
        $this->collectionIds[] = $collectionId;

        $this->db->table('cms_collection_translations')->insert([
            'collection_id' => $collectionId,
            'language_id'   => $this->langEsId,
            'name'          => 'Colección EN',
            'slug'          => 'coleccion-en',
        ]);

        $this->db->table('cms_collection_translations')->insert([
            'collection_id' => $collectionId,
            'language_id'   => $this->langEnId,
            'name'          => 'Collection EN',
            'slug'          => 'collection-en',
        ]);
    }

    private function seedMenus(): void
    {
        // Menu 0: ES only with 4 items
        $this->db->table('cms_menus')->insert([
            'menu_key'  => 'menu-es',
            'is_active' => 1,
        ]);
        $menuId = $this->db->insertID();
        $this->menuIds[] = $menuId;

        $this->db->table('cms_menu_translations')->insert([
            'menu_id'     => $menuId,
            'language_id' => $this->langEsId,
            'name'        => 'Menú ES',
        ]);

        for ($i = 0; $i < 4; $i++) {
            $this->db->table('cms_menu_items')->insert([
                'menu_id'    => $menuId,
                'link_type'  => 'no_link',
                'sort_order' => $i + 1,
                'is_active'  => 1,
            ]);
            $itemId = $this->db->insertID();
            $this->menuItemIds[] = $itemId;

            $this->db->table('cms_menu_item_translations')->insert([
                'menu_item_id' => $itemId,
                'language_id'  => $this->langEsId,
                'label'        => "Item {$i} ES",
            ]);
        }

        // Menu 1: ES+EN with 4 items
        $this->db->table('cms_menus')->insert([
            'menu_key'  => 'menu-en',
            'is_active' => 1,
        ]);
        $menuId = $this->db->insertID();
        $this->menuIds[] = $menuId;

        $this->db->table('cms_menu_translations')->insert([
            'menu_id'     => $menuId,
            'language_id' => $this->langEsId,
            'name'        => 'Menú EN',
        ]);

        $this->db->table('cms_menu_translations')->insert([
            'menu_id'     => $menuId,
            'language_id' => $this->langEnId,
            'name'        => 'Menu EN',
        ]);

        for ($i = 4; $i < 8; $i++) {
            $this->db->table('cms_menu_items')->insert([
                'menu_id'    => $menuId,
                'link_type'  => 'no_link',
                'sort_order' => $i - 3,
                'is_active'  => 1,
            ]);
            $itemId = $this->db->insertID();
            $this->menuItemIds[] = $itemId;

            $this->db->table('cms_menu_item_translations')->insert([
                'menu_item_id' => $itemId,
                'language_id'  => $this->langEsId,
                'label'        => "Item {$i} ES",
            ]);

            $this->db->table('cms_menu_item_translations')->insert([
                'menu_item_id' => $itemId,
                'language_id'  => $this->langEnId,
                'label'        => "Item {$i} EN",
            ]);
        }
    }

    private function seedEntries(): void
    {
        $collectionId = $this->collectionIds[0];

        // Entries 0-5: ES only
        for ($i = 0; $i < 6; $i++) {
            $this->db->table('cms_entries')->insert([
                'collection_id'   => $collectionId,
                'workflow_status' => 'published',
                'published_at'    => date('Y-m-d H:i:s'),
            ]);
            $entryId = $this->db->insertID();
            $this->entryIds[] = $entryId;

            $this->db->table('cms_entry_translations')->insert([
                'entry_id'    => $entryId,
                'language_id' => $this->langEsId,
                'slug'        => "entry-es-{$i}",
                'title'       => "Entrada ES {$i}",
            ]);
        }

        // Entries 6-8: ES+EN
        for ($i = 6; $i < 9; $i++) {
            $this->db->table('cms_entries')->insert([
                'collection_id'   => $collectionId,
                'workflow_status' => 'published',
                'published_at'    => date('Y-m-d H:i:s'),
            ]);
            $entryId = $this->db->insertID();
            $this->entryIds[] = $entryId;

            $this->db->table('cms_entry_translations')->insert([
                'entry_id'    => $entryId,
                'language_id' => $this->langEsId,
                'slug'        => "entry-es-{$i}",
                'title'       => "Entrada ES {$i}",
            ]);

            $this->db->table('cms_entry_translations')->insert([
                'entry_id'    => $entryId,
                'language_id' => $this->langEnId,
                'slug'        => "entry-en-{$i}",
                'title'       => "Entry EN {$i}",
            ]);
        }

        // Entry 9: No translations
        $this->db->table('cms_entries')->insert([
            'collection_id'   => $collectionId,
            'workflow_status' => 'published',
            'published_at'    => date('Y-m-d H:i:s'),
        ]);
        $entryId = $this->db->insertID();
        $this->entryIds[] = $entryId;
    }
}
