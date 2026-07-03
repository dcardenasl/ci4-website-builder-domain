<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Cms;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class PublicMenuControllerTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    private int $langEsId;
    private int $menuId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db->disableForeignKeyChecks();
        $this->db->query("DELETE FROM `cms_menu_item_translations`");
        $this->db->query("DELETE FROM `cms_menu_items`");
        $this->db->query("DELETE FROM `cms_menus`");
        $this->db->query("DELETE FROM `cms_languages`");
        $this->db->enableForeignKeyChecks();

        // Seed language
        $this->db->table('cms_languages')->insert([
            'code'       => 'es',
            'name'       => 'Spanish',
            'is_default' => 1,
            'is_active'  => 1,
        ]);
        $this->langEsId = $this->db->insertID();

        // Seed menu
        $this->db->table('cms_menus')->insert([
            'menu_key' => 'main-nav',
            'location' => 'header',
            'is_active' => 1,
        ]);
        $this->menuId = $this->db->insertID();
    }

    public function testGetPublicMenuTreeSuccess(): void
    {
        // Insert Parent Item
        $this->db->table('cms_menu_items')->insert([
            'menu_id' => $this->menuId,
            'link_type' => 'no_link',
            'link_target' => '_self',
            'sort_order' => 1,
            'is_active' => 1,
        ]);
        $parentItemId = $this->db->insertID();

        $this->db->table('cms_menu_item_translations')->insert([
            'menu_item_id' => $parentItemId,
            'language_id' => $this->langEsId,
            'label' => 'Inicio',
        ]);

        // Insert Child Item
        $this->db->table('cms_menu_items')->insert([
            'menu_id' => $this->menuId,
            'parent_id' => $parentItemId,
            'link_type' => 'custom_url',
            'link_target' => '_blank',
            'sort_order' => 1,
            'is_active' => 1,
        ]);
        $childItemId = $this->db->insertID();

        $this->db->table('cms_menu_item_translations')->insert([
            'menu_item_id' => $childItemId,
            'language_id' => $this->langEsId,
            'label' => 'Contacto',
            'custom_url' => 'https://google.com',
        ]);

        // Call the public menu endpoint
        $result = $this->withHeaders(['Accept-Language' => 'es'])->get('/api/v1/public/menus/main-nav');

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);
        $this->assertSame('success', $body['status']);
        $this->assertSame('main-nav', $body['data']['menu_key']);
        $this->assertCount(1, $body['data']['items']);

        $parentResolved = $body['data']['items'][0];
        $this->assertSame('Inicio', $parentResolved['label']);
        $this->assertCount(1, $parentResolved['children']);

        $childResolved = $parentResolved['children'][0];
        $this->assertSame('Contacto', $childResolved['label']);
        $this->assertSame('https://google.com', $childResolved['custom_url']);
    }

    public function testGetPublicMenuUsesTranslatedCollectionSlug(): void
    {
        $this->db->disableForeignKeyChecks();
        $this->db->query("DELETE FROM `cms_collection_translations`");
        $this->db->query("DELETE FROM `cms_collections`");
        $this->db->query("DELETE FROM `cms_entry_translations`");
        $this->db->query("DELETE FROM `cms_entries`");
        $this->db->enableForeignKeyChecks();

        $this->db->table('cms_collections')->insert([
            'collection_key' => 'news',
            'is_active'      => 1,
        ]);
        $collectionId = $this->db->insertID();

        $this->db->table('cms_collection_translations')->insert([
            'collection_id' => $collectionId,
            'language_id'   => $this->langEsId,
            'slug'          => 'noticias',
            'name'          => 'Noticias',
        ]);

        $this->db->table('cms_entries')->insert([
            'collection_id'  => $collectionId,
            'workflow_status' => 'published',
        ]);
        $entryId = $this->db->insertID();

        $this->db->table('cms_entry_translations')->insert([
            'entry_id'     => $entryId,
            'language_id'  => $this->langEsId,
            'slug'         => 'articulo-principal',
            'title'        => 'Artículo principal',
        ]);

        $this->db->table('cms_menu_items')->insert([
            'menu_id' => $this->menuId,
            'link_type' => 'collection_listing',
            'collection_id' => $collectionId,
            'link_target' => '_self',
            'sort_order' => 1,
            'is_active' => 1,
        ]);
        $collectionMenuItemId = $this->db->insertID();
        $this->db->table('cms_menu_item_translations')->insert([
            'menu_item_id' => $collectionMenuItemId,
            'language_id' => $this->langEsId,
            'label' => 'Noticias',
        ]);

        $this->db->table('cms_menu_items')->insert([
            'menu_id' => $this->menuId,
            'link_type' => 'entry',
            'entry_id' => $entryId,
            'link_target' => '_self',
            'sort_order' => 2,
            'is_active' => 1,
        ]);
        $entryMenuItemId = $this->db->insertID();
        $this->db->table('cms_menu_item_translations')->insert([
            'menu_item_id' => $entryMenuItemId,
            'language_id' => $this->langEsId,
            'label' => 'Artículo principal',
        ]);

        $result = $this->withHeaders(['Accept-Language' => 'es'])->get('/api/v1/public/menus/main-nav');

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);

        $this->assertSame('/noticias', $body['data']['items'][0]['custom_url']);
        $this->assertSame('/noticias/articulo-principal', $body['data']['items'][1]['custom_url']);
    }

    public function testGetPublicMenuHomePagePointsToLocalizedRoot(): void
    {
        $this->db->table('cms_pages')->insert([
            'page_type'    => 'home',
            'status'       => 'published',
            'deleted_at'   => null,
            'sort_order'   => 1,
            'is_in_sitemap' => 1,
        ]);
        $pageId = $this->db->insertID();

        $this->db->table('cms_page_translations')->insert([
            'page_id'          => $pageId,
            'language_id'      => $this->langEsId,
            'slug'             => 'home',
            'title'            => 'Inicio',
            'excerpt'          => null,
            'meta_title'       => null,
            'meta_description' => null,
            'og_image_file_id' => null,
            'og_type'          => null,
            'canonical_url'    => null,
            'robots'           => null,
            'schema_data'      => null,
        ]);

        $this->db->table('cms_menu_items')->insert([
            'menu_id' => $this->menuId,
            'link_type' => 'page',
            'page_id' => $pageId,
            'link_target' => '_self',
            'sort_order' => 1,
            'is_active' => 1,
        ]);
        $menuItemId = $this->db->insertID();

        $this->db->table('cms_menu_item_translations')->insert([
            'menu_item_id' => $menuItemId,
            'language_id' => $this->langEsId,
            'label' => 'Inicio',
        ]);

        $result = $this->withHeaders(['Accept-Language' => 'es'])->get('/api/v1/public/menus/main-nav');

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);

        $this->assertSame('/', $body['data']['items'][0]['custom_url']);
    }

    public function testGetPublicMenuNotFound(): void
    {
        $result = $this->get('/api/v1/public/menus/no-existe');
        $result->assertStatus(404);
    }
}
