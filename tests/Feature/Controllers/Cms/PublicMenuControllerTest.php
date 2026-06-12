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
        $this->db->table('cms_menu_item_translations')->truncate();
        $this->db->table('cms_menu_items')->truncate();
        $this->db->table('cms_menus')->truncate();
        $this->db->table('cms_languages')->truncate();
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

    public function testGetPublicMenuNotFound(): void
    {
        $result = $this->get('/api/v1/public/menus/no-existe');
        $result->assertStatus(404);
    }
}
