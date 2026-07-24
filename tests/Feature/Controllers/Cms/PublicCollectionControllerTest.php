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
}
