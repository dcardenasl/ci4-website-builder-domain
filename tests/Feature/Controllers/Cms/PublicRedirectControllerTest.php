<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Cms;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

/**
 * @internal
 */
final class PublicRedirectControllerTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    private int $langEsId;
    private int $pageId;
    private int $collectionId;
    private int $entryId;

    protected function setUp(): void
    {
        parent::setUp();

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

        // 1. Seed languages
        $db->table('cms_languages')->insert([
            'code'        => 'es',
            'name'        => 'Spanish',
            'native_name' => 'Español',
            'is_default'  => 1,
            'is_active'   => 1,
        ]);
        $this->langEsId = $db->insertID();

        // 2. Seed pages
        $db->table('cms_pages')->insert([
            'status' => 'published',
        ]);
        $this->pageId = $db->insertID();

        $db->table('cms_page_translations')->insert([
            'page_id'     => $this->pageId,
            'language_id' => $this->langEsId,
            'slug'        => 'nosotros-nuevo',
            'title'       => 'Sobre Nosotros',
        ]);

        // 3. Seed collections & entries
        $db->table('cms_collections')->insert([
            'collection_key' => 'blog',
            'is_active'      => 1,
        ]);
        $this->collectionId = $db->insertID();

        $db->table('cms_collection_translations')->insert([
            'collection_id' => $this->collectionId,
            'language_id'   => $this->langEsId,
            'slug'          => 'noticias',
            'name'          => 'Noticias',
        ]);

        $db->table('cms_entries')->insert([
            'collection_id'   => $this->collectionId,
            'workflow_status' => 'published',
        ]);
        $this->entryId = $db->insertID();

        $db->table('cms_entry_translations')->insert([
            'entry_id'    => $this->entryId,
            'language_id' => $this->langEsId,
            'slug'        => 'nuevo-post',
            'title'       => 'Nuevo Post',
        ]);
    }

    public function testResolveManualRedirect(): void
    {
        $db = Database::connect();
        $db->table('cms_redirects')->insert([
            'old_path'      => 'contacto-viejo',
            'new_url'       => 'https://google.com/contact',
            'redirect_type' => 302,
            'is_active'     => 1,
        ]);

        $result = $this->get('/api/v1/public/redirects/contacto-viejo');
        $result->assertStatus(200);

        $body = json_decode($result->getJSON(), true);
        $this->assertSame('success', $body['status']);
        $this->assertSame('https://google.com/contact', $body['data']['new_url']);
        $this->assertSame(302, $body['data']['redirect_type']);
    }

    public function testResolvePageSlugRedirect(): void
    {
        $db = Database::connect();
        $db->table('cms_slug_redirects')->insert([
            'entity_type'   => 'page',
            'entity_id'     => $this->pageId,
            'language_id'   => $this->langEsId,
            'old_slug'      => 'nosotros-viejo',
            'old_full_path' => 'nosotros-viejo',
            'created_at'    => date('Y-m-d H:i:s'),
        ]);

        $result = $this->get('/api/v1/public/redirects/nosotros-viejo');
        $result->assertStatus(200);

        $body = json_decode($result->getJSON(), true);
        $this->assertSame('success', $body['status']);
        $this->assertSame('/es/pages/nosotros-nuevo', $body['data']['new_url']);
        $this->assertSame(301, $body['data']['redirect_type']);
    }

    public function testResolveEntrySlugRedirect(): void
    {
        $db = Database::connect();
        $db->table('cms_slug_redirects')->insert([
            'entity_type'   => 'entry',
            'entity_id'     => $this->entryId,
            'language_id'   => $this->langEsId,
            'old_slug'      => 'viejo-post',
            'old_full_path' => 'noticias/viejo-post',
            'created_at'    => date('Y-m-d H:i:s'),
        ]);

        $result = $this->get('/api/v1/public/redirects/noticias/viejo-post');
        $result->assertStatus(200);

        $body = json_decode($result->getJSON(), true);
        $this->assertSame('success', $body['status']);
        $this->assertSame('/es/entries/noticias/nuevo-post', $body['data']['new_url']);
        $this->assertSame(301, $body['data']['redirect_type']);
    }

    public function testResolveRedirectNotFound(): void
    {
        $result = $this->get('/api/v1/public/redirects/no-existe');
        $result->assertStatus(404);
    }
}
