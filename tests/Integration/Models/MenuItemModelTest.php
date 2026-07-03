<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Models\MenuItemModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Smoke tests for MenuItemModel. Extend with persistence scenarios as
 * domain behavior solidifies.
 *
 * @internal
 */
final class MenuItemModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    protected function setUp(): void
    {
        parent::setUp();

        $this->db->disableForeignKeyChecks();
        $this->db->query("DELETE FROM `cms_menu_item_translations`");
        $this->db->query("DELETE FROM `cms_menu_items`");
        $this->db->query("DELETE FROM `cms_menus`");
        $this->db->enableForeignKeyChecks();
    }

    public function testModelReportsCorrectTable(): void
    {
        $model = new MenuItemModel();

        $this->assertSame('cms_menu_items', $model->getTable());
    }

    public function testModelAllowsFilteringByMenuId(): void
    {
        $this->db->table('cms_menus')->insert([
            'menu_key' => 'main',
            'location' => 'header',
            'is_active' => 1,
        ]);
        $mainMenuId = $this->db->insertID();

        $this->db->table('cms_menus')->insert([
            'menu_key' => 'footer',
            'location' => 'footer',
            'is_active' => 1,
        ]);
        $footerMenuId = $this->db->insertID();

        $this->db->table('cms_menu_items')->insert([
            'menu_id' => $mainMenuId,
            'link_type' => 'no_link',
            'link_target' => '_self',
            'sort_order' => 1,
            'is_active' => 1,
        ]);

        $this->db->table('cms_menu_items')->insert([
            'menu_id' => $footerMenuId,
            'link_type' => 'no_link',
            'link_target' => '_self',
            'sort_order' => 1,
            'is_active' => 1,
        ]);

        $model = new MenuItemModel();
        $items = $model->applyFilters(['menu_id' => $mainMenuId])->findAll();

        $this->assertCount(1, $items);
        $this->assertSame($mainMenuId, (int) $items[0]->menu_id);
    }
}
