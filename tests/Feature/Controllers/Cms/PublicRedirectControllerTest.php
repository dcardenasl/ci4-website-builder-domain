<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Cms;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;
use Tests\Support\Fixtures\CmsFixtureFactory;
use Tests\Support\Traits\WithWebAppKeyTrait;

/**
 * @internal
 */
final class PublicRedirectControllerTest extends CIUnitTestCase
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

    /** @var array{id:int,translations:list<array<string,mixed>>} */
    private array $page;

    /** @var array{id:int,key:string,translations:list<array<string,mixed>>} */
    private array $collection;

    private int $entryId;

    private string $pageSlug;

    private string $collectionSlug;

    private string $entrySlug;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configureWebAppKey();

        $db = Database::connect();
        $db->disableForeignKeyChecks();
        $db->query("DELETE FROM `cms_slug_redirects`");
        $db->query("DELETE FROM `cms_redirects`");
        $db->query("DELETE FROM `cms_page_translations`");
        $db->query("DELETE FROM `cms_pages`");
        $db->query("DELETE FROM `cms_collection_translations`");
        $db->query("DELETE FROM `cms_entry_translations`");
        $db->query("DELETE FROM `cms_entries`");
        $db->query("DELETE FROM `cms_collections`");
        $db->query("DELETE FROM `cms_languages`");
        $db->enableForeignKeyChecks();

        $this->fixtures = new CmsFixtureFactory($db, self::class);
        $this->languages = $this->fixtures->languages(3);
        $language = $this->languages[0];
        $this->pageSlug = $this->fixtures->slug('page', $language['code']);
        $this->collectionSlug = $this->fixtures->slug('collection', $language['code']);
        $this->entrySlug = $this->fixtures->slug('entry', $language['code']);

        $this->page = $this->fixtures->page([[
            'language_id' => $language['id'],
            'slug' => $this->pageSlug,
            'title' => $this->fixtures->text('page-title', $language['code']),
        ]]);
        $this->collection = $this->fixtures->collection([[
            'language_id' => $language['id'],
            'slug' => $this->collectionSlug,
            'name' => $this->fixtures->text('collection-name', $language['code']),
        ]], ['collection_key' => $this->fixtures->slug('collection-key')]);

        $db->table('cms_entries')->insert([
            'collection_id' => $this->collection['id'],
            'workflow_status' => 'published',
        ]);
        $this->entryId = (int) $db->insertID();
        $db->table('cms_entry_translations')->insert([
            'entry_id' => $this->entryId,
            'language_id' => $language['id'],
            'slug' => $this->entrySlug,
            'title' => $this->fixtures->text('entry-title', $language['code']),
        ]);
    }

    protected function tearDown(): void
    {
        $this->restoreWebAppKey();
        parent::tearDown();
    }

    public function testResolveManualRedirect(): void
    {
        $oldPath = $this->fixtures->slug('old-manual-path');
        $newUrl = 'https://example.com/' . $this->fixtures->slug('destination');
        $db = Database::connect();
        $db->table('cms_redirects')->insert([
            'old_path' => $oldPath,
            'new_url' => $newUrl,
            'redirect_type' => 302,
            'is_active' => 1,
        ]);

        $result = $this->get('/api/v1/public/redirects/' . $oldPath);
        $result->assertStatus(200);

        $body = json_decode($result->getJSON(), true);
        $this->assertSame('success', $body['status']);
        $this->assertSame($newUrl, $body['data']['new_url']);
        $this->assertSame(302, $body['data']['redirect_type']);
    }

    public function testResolvePageSlugRedirect(): void
    {
        $oldSlug = $this->fixtures->slug('old-page');
        $db = Database::connect();
        $db->table('cms_slug_redirects')->insert([
            'entity_type' => 'page',
            'entity_id' => $this->page['id'],
            'language_id' => $this->languages[0]['id'],
            'old_slug' => $oldSlug,
            'old_full_path' => $oldSlug,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $result = $this->get('/api/v1/public/redirects/' . $oldSlug);
        $result->assertStatus(200);

        $body = json_decode($result->getJSON(), true);
        $this->assertSame('success', $body['status']);
        $this->assertSame('/' . $this->languages[0]['code'] . '/pages/' . $this->pageSlug, $body['data']['new_url']);
        $this->assertSame(301, $body['data']['redirect_type']);
    }

    public function testResolveEntrySlugRedirect(): void
    {
        $oldSlug = $this->fixtures->slug('old-entry');
        $oldFullPath = $this->collectionSlug . '/' . $oldSlug;
        $db = Database::connect();
        $db->table('cms_slug_redirects')->insert([
            'entity_type' => 'entry',
            'entity_id' => $this->entryId,
            'language_id' => $this->languages[0]['id'],
            'old_slug' => $oldSlug,
            'old_full_path' => $oldFullPath,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $result = $this->get('/api/v1/public/redirects/' . $oldFullPath);
        $result->assertStatus(200);

        $body = json_decode($result->getJSON(), true);
        $this->assertSame('success', $body['status']);
        $this->assertSame(
            '/' . $this->languages[0]['code'] . '/entries/' . $this->collectionSlug . '/' . $this->entrySlug,
            $body['data']['new_url'],
        );
        $this->assertSame(301, $body['data']['redirect_type']);
    }

    public function testResolveRedirectNotFound(): void
    {
        $result = $this->get('/api/v1/public/redirects/' . $this->fixtures->slug('missing-path'));

        $result->assertStatus(404);
    }
}
