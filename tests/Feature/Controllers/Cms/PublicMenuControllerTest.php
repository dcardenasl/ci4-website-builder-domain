<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Cms;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Traits\WithWebAppKeyTrait;

/**
 * @internal
 */
final class PublicMenuControllerTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use WithWebAppKeyTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    private int $langPrimaryId;
    private int $langSecondaryId;
    private string $primaryCode;
    private string $secondaryCode;
    private int $menuId;
    private string $menuKey;
    private string $fixturePrefix;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configureWebAppKey();

        $token = bin2hex(random_bytes(4));
        $this->fixturePrefix = 'fixture' . $token;
        $this->primaryCode = 'x' . $token[0];
        $this->secondaryCode = 'y' . $token[1];
        $this->menuKey = $this->fixtureSlug('menu');

        $this->db->disableForeignKeyChecks();
        $this->db->query("DELETE FROM `cms_menu_item_translations`");
        $this->db->query("DELETE FROM `cms_menu_items`");
        $this->db->query("DELETE FROM `cms_menus`");
        $this->db->query("DELETE FROM `cms_languages`");
        $this->db->enableForeignKeyChecks();

        // Seed language
        $this->db->table('cms_languages')->insert([
            'code'       => $this->primaryCode,
            'name'       => $this->fixtureText('language-a'),
            'is_default' => 1,
            'is_active'  => 1,
        ]);
        $this->langPrimaryId = $this->db->insertID();

        $this->db->table('cms_languages')->insert([
            'code'       => $this->secondaryCode,
            'name'       => $this->fixtureText('language-b'),
            'is_default' => 0,
            'is_active'  => 1,
        ]);
        $this->langSecondaryId = $this->db->insertID();

        // Seed menu
        $this->db->table('cms_menus')->insert([
            'menu_key' => $this->menuKey,
            'location' => 'header',
            'is_active' => 1,
        ]);
        $this->menuId = $this->db->insertID();
    }

    protected function tearDown(): void
    {
        $this->restoreWebAppKey();
        parent::tearDown();
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
        $parentLabel = $this->fixtureText('parent-item');

        $this->db->table('cms_menu_item_translations')->insert([
            'menu_item_id' => $parentItemId,
            'language_id' => $this->langPrimaryId,
            'label' => $parentLabel,
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
        $childLabel = $this->fixtureText('child-item');
        $customUrl = 'https://' . $this->fixturePrefix . '.test/target';

        $this->db->table('cms_menu_item_translations')->insert([
            'menu_item_id' => $childItemId,
            'language_id' => $this->langPrimaryId,
            'label' => $childLabel,
            'custom_url' => $customUrl,
        ]);

        // Call the public menu endpoint
        $result = $this->withHeaders(['Accept-Language' => $this->primaryCode, ...$this->webAppKeyHeader()])->get('/api/v1/public/menus/' . $this->menuKey);

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);
        $this->assertSame('success', $body['status']);
        $this->assertSame($this->menuKey, $body['data']['menu_key']);
        $this->assertCount(1, $body['data']['items']);

        $parentResolved = $body['data']['items'][0];
        $this->assertSame($parentLabel, $parentResolved['label']);
        $this->assertCount(1, $parentResolved['children']);

        $childResolved = $parentResolved['children'][0];
        $this->assertSame($childLabel, $childResolved['label']);
        $this->assertSame($customUrl, $childResolved['custom_url']);
    }

    public function testGetPublicMenuResolvesMenuAndItemTranslationsByLanguage(): void
    {
        $primaryMenuName = $this->fixtureText('menu-name-a');
        $secondaryMenuName = $this->fixtureText('menu-name-b');
        $primaryItemLabel = $this->fixtureText('item-label-a');
        $secondaryItemLabel = $this->fixtureText('item-label-b');

        $this->db->table('cms_menu_translations')->insertBatch([
            [
                'menu_id'     => $this->menuId,
                'language_id' => $this->langPrimaryId,
                'name'        => $primaryMenuName,
            ],
            [
                'menu_id'     => $this->menuId,
                'language_id' => $this->langSecondaryId,
                'name'        => $secondaryMenuName,
            ],
        ]);

        $this->db->table('cms_menu_items')->insert([
            'menu_id'     => $this->menuId,
            'link_type'   => 'no_link',
            'link_target' => '_self',
            'sort_order'  => 1,
            'is_active'   => 1,
        ]);
        $itemId = $this->db->insertID();

        $this->db->table('cms_menu_item_translations')->insertBatch([
            [
                'menu_item_id' => $itemId,
                'language_id'  => $this->langPrimaryId,
                'label'        => $primaryItemLabel,
            ],
            [
                'menu_item_id' => $itemId,
                'language_id'  => $this->langSecondaryId,
                'label'        => $secondaryItemLabel,
            ],
        ]);

        $primary = $this->withHeaders(['Accept-Language' => $this->primaryCode, ...$this->webAppKeyHeader()])
            ->get('/api/v1/public/menus/' . $this->menuKey);
        $primaryBody = json_decode($primary->getJSON(), true);

        $secondary = $this->withHeaders(['Accept-Language' => $this->secondaryCode, ...$this->webAppKeyHeader()])
            ->get('/api/v1/public/menus/' . $this->menuKey);
        $secondaryBody = json_decode($secondary->getJSON(), true);

        $this->assertSame($primaryMenuName, $primaryBody['data']['name']);
        $this->assertSame($primaryItemLabel, $primaryBody['data']['items'][0]['label']);
        $this->assertSame($secondaryMenuName, $secondaryBody['data']['name']);
        $this->assertSame($secondaryItemLabel, $secondaryBody['data']['items'][0]['label']);
    }

    public function testGetPublicMenuUsesTranslatedCollectionSlug(): void
    {
        $this->db->disableForeignKeyChecks();
        $this->db->query("DELETE FROM `cms_collection_translations`");
        $this->db->query("DELETE FROM `cms_collections`");
        $this->db->query("DELETE FROM `cms_entry_translations`");
        $this->db->query("DELETE FROM `cms_entries`");
        $this->db->enableForeignKeyChecks();

        $collectionKey = $this->fixtureSlug('collection');
        $collectionSlug = $this->fixtureSlug('collection-path');
        $collectionName = $this->fixtureText('collection-name');

        $this->db->table('cms_collections')->insert([
            'collection_key' => $collectionKey,
            'is_active'      => 1,
        ]);
        $collectionId = $this->db->insertID();

        $this->db->table('cms_collection_translations')->insert([
            'collection_id' => $collectionId,
            'language_id'   => $this->langPrimaryId,
            'slug'          => $collectionSlug,
            'name'          => $collectionName,
        ]);

        $this->db->table('cms_entries')->insert([
            'collection_id'  => $collectionId,
            'workflow_status' => 'published',
        ]);
        $entryId = $this->db->insertID();

        $entrySlug = $this->fixtureSlug('entry');
        $entryTitle = $this->fixtureText('entry-title');
        $this->db->table('cms_entry_translations')->insert([
            'entry_id'     => $entryId,
            'language_id'  => $this->langPrimaryId,
            'slug'         => $entrySlug,
            'title'        => $entryTitle,
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
            'language_id' => $this->langPrimaryId,
            'label'        => $collectionName,
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
            'language_id' => $this->langPrimaryId,
            'label'        => $entryTitle,
        ]);

        $result = $this->withHeaders(['Accept-Language' => $this->primaryCode, ...$this->webAppKeyHeader()])->get('/api/v1/public/menus/' . $this->menuKey);

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);

        $this->assertSame('/' . $collectionSlug, $body['data']['items'][0]['custom_url']);
        $this->assertSame('/' . $collectionSlug . '/' . $entrySlug, $body['data']['items'][1]['custom_url']);
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
            'language_id'      => $this->langPrimaryId,
            'slug'             => 'home',
            'title'            => $this->fixtureText('home-title'),
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
            'language_id' => $this->langPrimaryId,
            'label' => $this->fixtureText('home-item'),
        ]);

        $result = $this->withHeaders(['Accept-Language' => $this->primaryCode, ...$this->webAppKeyHeader()])->get('/api/v1/public/menus/' . $this->menuKey);

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);

        $this->assertSame('/', $body['data']['items'][0]['custom_url']);
    }

    public function testGetPublicMenuNotFound(): void
    {
        $result = $this->get('/api/v1/public/menus/' . $this->fixtureSlug('missing'));
        $result->assertStatus(404);
    }

    private function fixtureText(string $suffix): string
    {
        return $this->fixturePrefix . '-' . $suffix;
    }

    private function fixtureSlug(string $suffix): string
    {
        return strtolower($this->fixtureText($suffix));
    }
}
