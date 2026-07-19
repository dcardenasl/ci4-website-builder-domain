<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Cms;

use App\DTO\Request\Cms\MenuItemCreateRequestDTO;
use App\DTO\Request\Cms\MenuItemUpdateRequestDTO;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Services;

/**
 * @internal
 */
final class MenuItemServiceHierarchyTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    private int $menuId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db->disableForeignKeyChecks();
        $this->db->query("DELETE FROM `cms_menu_item_translations`");
        $this->db->query("DELETE FROM `cms_menu_items`");
        $this->db->query("DELETE FROM `cms_menus`");
        $this->db->enableForeignKeyChecks();

        // Seed menu
        $this->db->table('cms_menus')->insert([
            'menu_key' => 'main-nav',
            'location' => 'header',
            'is_active' => 1,
        ]);
        $this->menuId = $this->db->insertID();
    }

    public function testCircularHierarchyThrowsException(): void
    {
        $service = Services::menuItemService();

        // Insert first item
        $this->db->table('cms_menu_items')->insert([
            'menu_id' => $this->menuId,
            'link_type' => 'no_link',
            'link_target' => '_self',
            'sort_order' => 1,
            'is_active' => 1,
        ]);
        $item1Id = $this->db->insertID();

        // Insert second item under first
        $this->db->table('cms_menu_items')->insert([
            'menu_id' => $this->menuId,
            'parent_id' => (int) $item1Id,
            'link_type' => 'no_link',
            'link_target' => '_self',
            'sort_order' => 2,
            'is_active' => 1,
        ]);
        $item2Id = $this->db->insertID();

        // Try to update first item to have second item as parent (circular!)
        $this->expectException(\dcardenasl\Ci4ApiCore\Exceptions\ValidationException::class);

        $requestDto = new MenuItemUpdateRequestDTO([
            'parent_id' => (int) $item2Id,
        ], Services::validation());
        $service->update((int) $item1Id, $requestDto);
    }

    public function testInvalidLinkConstraintsThrowException(): void
    {
        $service = Services::menuItemService();

        // page link_type requires page_id to be not null
        $this->expectException(\dcardenasl\Ci4ApiCore\Exceptions\ValidationException::class);

        $requestDto = new MenuItemCreateRequestDTO([
            'menu_id' => $this->menuId,
            'link_type' => 'page',
            'page_id' => null, // invalid!
            'link_target' => '_self',
            'sort_order' => 1,
            'is_active' => 1,
        ], Services::validation());
        $service->store($requestDto);
    }

    public function testDuplicateNavigableMenuItemInSameMenuThrowsException(): void
    {
        $service = Services::menuItemService();

        // cms_menu_items.page_id has a FK to cms_pages.id — insert a real page
        // instead of hardcoding an assumed id, so this test doesn't depend on
        // what other fixtures happen to have inserted into a shared test DB.
        $this->db->table('cms_pages')->insert(['page_type' => 'generic']);
        $pageId = (int) $this->db->insertID();

        $this->db->table('cms_menu_items')->insert([
            'menu_id' => $this->menuId,
            'link_type' => 'page',
            'page_id' => $pageId,
            'link_target' => '_self',
            'sort_order' => 1,
            'is_active' => 1,
        ]);

        $this->expectException(\dcardenasl\Ci4ApiCore\Exceptions\ValidationException::class);

        $requestDto = new MenuItemCreateRequestDTO([
            'menu_id' => $this->menuId,
            'link_type' => 'page',
            'page_id' => $pageId,
            'link_target' => '_self',
            'sort_order' => 2,
            'is_active' => 1,
        ], Services::validation());

        $service->store($requestDto);
    }
}
