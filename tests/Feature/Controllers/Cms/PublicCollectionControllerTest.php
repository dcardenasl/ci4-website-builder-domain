<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Cms;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Fixtures\CmsFixtureFactory;
use Tests\Support\Traits\WithWebAppKeyTrait;

/**
 * @internal
 */
final class PublicCollectionControllerTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use WithWebAppKeyTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    private CmsFixtureFactory $fixtures;

    /** @var list<array{id:int,code:string,name:string,is_default:bool}> */
    private array $languages;

    /** @var array{id:int,key:string,translations:list<array<string,mixed>>} */
    private array $collection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configureWebAppKey();

        $this->db->disableForeignKeyChecks();
        $this->db->query("DELETE FROM `public_slugs`");
        $this->db->query("DELETE FROM `cms_collection_translations`");
        $this->db->query("DELETE FROM `cms_collections`");
        $this->db->query("DELETE FROM `cms_languages`");
        $this->db->enableForeignKeyChecks();

        $this->fixtures = new CmsFixtureFactory($this->db, self::class);
        $this->languages = $this->fixtures->languages(2);
        $this->collection = $this->fixtures->collection([
            [
                'language_id' => $this->languages[0]['id'],
                'slug' => $this->fixtures->slug('collection', $this->languages[0]['code']),
                'name' => $this->fixtures->text('collection-name', $this->languages[0]['code']),
                'description' => $this->fixtures->text('collection-description', $this->languages[0]['code']),
            ],
            [
                'language_id' => $this->languages[1]['id'],
                'slug' => $this->fixtures->slug('collection', $this->languages[1]['code']),
                'name' => $this->fixtures->text('collection-name', $this->languages[1]['code']),
                'description' => $this->fixtures->text('collection-description', $this->languages[1]['code']),
            ],
        ]);
    }

    protected function tearDown(): void
    {
        $this->restoreWebAppKey();
        parent::tearDown();
    }

    public function testGetPublicCollectionsSuccess(): void
    {
        $result = $this->get('/api/v1/public/' . $this->languages[0]['code'] . '/collections');

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);
        $primary = $this->collection['translations'][0];
        $secondary = $this->collection['translations'][1];

        $this->assertSame('success', $body['status']);
        $this->assertCount(1, $body['data']);
        $this->assertSame($this->collection['key'], $body['data'][0]['collection_key']);
        $this->assertSame($primary['slug'], $body['data'][0]['slug']);
        $this->assertSame($primary['name'], $body['data'][0]['name']);
        $this->assertSame($primary['description'], $body['data'][0]['description']);
        $this->assertSame($primary['slug'], $body['data'][0]['localized_slugs'][$this->languages[0]['code']]);
        $this->assertSame($secondary['slug'], $body['data'][0]['localized_slugs'][$this->languages[1]['code']]);
        $this->assertArrayNotHasKey('url_prefix', $body['data'][0]);
    }

    public function testBackfillCreatesGenericLocaleSlugsUsedByPublicResolution(): void
    {
        command('cms:backfill-public-slugs');

        $slugs = $this->db->table('public_slugs')
            ->where('resource_type', 'collection')
            ->where('resource_id', $this->collection['id'])
            ->orderBy('locale', 'ASC')
            ->get()
            ->getResultArray();

        $this->assertCount(2, $slugs);
        $result = $this->get('/api/v1/public/' . $this->languages[1]['code'] . '/collections');
        $result->assertStatus(200);

        $body = json_decode($result->getJSON(), true);
        $this->assertSame(
            $this->collection['translations'][1]['slug'],
            $body['data'][0]['localized_slugs'][$this->languages[1]['code']],
        );
    }

    /**
     * COL-002: a custom `entry_cta_label` per language must reach the public API response
     * (consumed by the web app's collection_listing block instead of its collection_type-based
     * default).
     */
    public function testGetPublicCollectionsExposesEntryCtaLabel(): void
    {
        $ctaLabel = $this->fixtures->text('cta-label', $this->languages[0]['code']);

        $this->db->table('cms_collection_translations')
            ->where('collection_id', $this->collection['id'])
            ->where('language_id', $this->languages[0]['id'])
            ->update(['entry_cta_label' => $ctaLabel]);

        $result = $this->get('/api/v1/public/' . $this->languages[0]['code'] . '/collections');

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);

        $this->assertSame($ctaLabel, $body['data'][0]['entry_cta_label']);
    }

    public function testGetPublicCollectionsExposesNullEntryCtaLabelWhenUnset(): void
    {
        $result = $this->get('/api/v1/public/' . $this->languages[0]['code'] . '/collections');

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);

        $this->assertArrayHasKey('entry_cta_label', $body['data'][0]);
        $this->assertNull($body['data'][0]['entry_cta_label']);
    }

    public function testGetPublicCollectionsFallsBackToNameWhenListingTitleIsEmpty(): void
    {
        $fallbackName = $this->fixtures->text('fallback-name');

        $this->db->table('cms_collection_translations')
            ->where('collection_id', $this->collection['id'])
            ->where('language_id', $this->languages[0]['id'])
            ->update([
                'slug' => $this->fixtures->slug('fallback-slug'),
                'name' => $fallbackName,
                'listing_title' => '',
            ]);

        $result = $this->get('/api/v1/public/' . $this->languages[0]['code'] . '/collections');

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);

        $this->assertSame($fallbackName, $body['data'][0]['listing_title']);
    }

    public function testPublicCollectionsSupportSparseFieldsets(): void
    {
        $result = $this->get(
            '/api/v1/public/' . $this->languages[0]['code'] . '/collections?fields=id,name,localized_slugs'
        );

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);
        $this->assertSame(
            ['id', 'name', 'localized_slugs'],
            array_keys($body['data'][0]),
        );
    }

    public function testPublicLayoutAndPageBootstrapComposeColdPageData(): void
    {
        $pageSlug = $this->fixtures->slug('bootstrap-page', $this->languages[0]['code']);
        $pageTitle = $this->fixtures->text('bootstrap-page-title', $this->languages[0]['code']);
        $this->fixtures->page([
            [
                'language_id' => $this->languages[0]['id'],
                'slug'        => $pageSlug,
                'title'       => $pageTitle,
            ],
        ]);

        $layout = $this->get('/api/v1/public/layout');
        $layout->assertStatus(200);
        $layoutBody = json_decode($layout->getJSON(), true);
        $this->assertSame(['main', 'footer', 'legal'], array_keys($layoutBody['data']['menus']));

        $bootstrap = $this->withHeaders([
            'Accept-Language' => $this->languages[0]['code'],
            ...$this->webAppKeyHeader(),
        ])->get('/api/v1/public/page-bootstrap/' . $pageSlug);
        $bootstrap->assertStatus(200);
        $bootstrapBody = json_decode($bootstrap->getJSON(), true);
        $this->assertSame('page', $bootstrapBody['data']['route']['type']);
        $this->assertSame($pageTitle, $bootstrapBody['data']['route']['data']['title']);
        $this->assertArrayHasKey('settings', $bootstrapBody['data']['layout']);
    }

    public function testPageBootstrapPreservesLocalizedCollectionAndEntrySlugs(): void
    {
        $collectionIndexEs = 'coleccion-es';
        $collectionIndexEn = 'collection-en';
        $entrySlugEs = 'entrada-es';
        $entrySlugEn = 'entry-en';
        $entryTitleEs = 'Entrada en español';
        $entryTitleEn = 'Entry in English';

        $this->fixtures->page([
            [
                'language_id' => $this->languages[0]['id'],
                'slug'        => $collectionIndexEs,
                'title'       => 'Colección',
            ],
            [
                'language_id' => $this->languages[1]['id'],
                'slug'        => $collectionIndexEn,
                'title'       => 'Collection',
            ],
        ], [
            'collection_id' => $this->collection['id'],
            'page_type'     => 'collection_index',
        ]);

        $this->fixtures->entry($this->collection['id'], [
            [
                'language_id' => $this->languages[0]['id'],
                'slug'        => $entrySlugEs,
                'title'       => $entryTitleEs,
            ],
            [
                'language_id' => $this->languages[1]['id'],
                'slug'        => $entrySlugEn,
                'title'       => $entryTitleEn,
            ],
        ]);

        $bootstrapEs = $this->withHeaders([
            'Accept-Language' => $this->languages[0]['code'],
            ...$this->webAppKeyHeader(),
        ])->get('/api/v1/public/page-bootstrap/' . $collectionIndexEs . '/' . $entrySlugEs);

        $bootstrapEs->assertStatus(200);
        $bodyEs = json_decode($bootstrapEs->getJSON(), true);
        $this->assertSame('entry', $bodyEs['data']['route']['type']);
        $this->assertSame($entrySlugEs, $bodyEs['data']['route']['data']['slug']);
        $this->assertSame($entryTitleEs, $bodyEs['data']['route']['data']['title']);

        $bootstrapEn = $this->withHeaders([
            'Accept-Language' => $this->languages[1]['code'],
            ...$this->webAppKeyHeader(),
        ])->get('/api/v1/public/page-bootstrap/' . $collectionIndexEn . '/' . $entrySlugEn);

        $bootstrapEn->assertStatus(200);
        $bodyEn = json_decode($bootstrapEn->getJSON(), true);
        $this->assertSame('entry', $bodyEn['data']['route']['type']);
        $this->assertSame($entrySlugEn, $bodyEn['data']['route']['data']['slug']);
        $this->assertSame($entryTitleEn, $bodyEn['data']['route']['data']['title']);
    }
}
