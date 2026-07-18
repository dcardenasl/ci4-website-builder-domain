<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Cms;

use App\Entities\MenuItemEntity;
use App\Interfaces\Cms\MenuItemServiceInterface;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Services;
use dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface;
use dcardenasl\Ci4ApiCore\Repositories\RepositoryInterface;

/**
 * Tests for MenuItemService::resolveLink() and link resolution logic.
 *
 * @internal
 */
final class MenuItemServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    private \App\Services\Cms\MenuItemService $service;
    private int $langEsId;
    private int $pageId;
    private int $entryId;
    private int $collectionId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = Services::menuItemService();

        // Truncate relevant tables first
        $this->db->disableForeignKeyChecks();
        $this->db->query("DELETE FROM `cms_entry_translations`");
        $this->db->query("DELETE FROM `cms_entries`");
        $this->db->query("DELETE FROM `cms_collection_translations`");
        $this->db->query("DELETE FROM `cms_collections`");
        $this->db->query("DELETE FROM `cms_page_translations`");
        $this->db->query("DELETE FROM `cms_pages`");
        $this->db->query("DELETE FROM `cms_languages`");
        $this->db->enableForeignKeyChecks();

        // Setup languages
        $this->db->table('cms_languages')->insert([
            'code'       => 'es',
            'name'       => 'Spanish',
            'is_default' => 1,
            'is_active'  => 1,
        ]);
        $this->langEsId = $this->db->insertID();

        // Setup page
        $this->db->table('cms_pages')->insert([
            'page_type'   => 'generic',
            'status'      => 'published',
            'sort_order'  => 1,
        ]);
        $this->pageId = $this->db->insertID();

        // Setup page translation
        $this->db->table('cms_page_translations')->insert([
            'page_id'    => $this->pageId,
            'language_id' => $this->langEsId,
            'title'      => 'Acerca de',
            'slug'       => 'about',
        ]);

        // Setup collection
        $this->db->table('cms_collections')->insert([
            'collection_key'    => 'blog',
            'is_active'         => 1,
            'requires_approval' => 0,
        ]);
        $this->collectionId = $this->db->insertID();

        // Setup collection translation
        $this->db->table('cms_collection_translations')->insert([
            'collection_id' => $this->collectionId,
            'language_id'   => $this->langEsId,
            'name'          => 'Blog',
            'slug'          => 'blog',
        ]);

        // Setup entry
        $this->db->table('cms_entries')->insert([
            'collection_id'  => $this->collectionId,
            'workflow_status' => 'published',
        ]);
        $this->entryId = $this->db->insertID();

        // Setup entry translation
        $this->db->table('cms_entry_translations')->insert([
            'entry_id'    => $this->entryId,
            'language_id' => $this->langEsId,
            'slug'        => 'first-post',
            'title'       => 'Primer Post',
        ]);
    }

    public function testServiceImplementsItsInterface(): void
    {
        $this->assertInstanceOf(MenuItemServiceInterface::class, $this->service);
    }

    public function testResolveLinkForPage(): void
    {
        $item = new MenuItemEntity([
            'id'        => 1,
            'link_type' => 'page',
            'page_id'   => $this->pageId,
        ]);

        $url = $this->service->resolveLink($item, 'es');

        $this->assertSame('/about', $url);
    }

    public function testResolveLinkForPageHome(): void
    {
        // Setup home page
        $this->db->table('cms_pages')->insert([
            'id'          => 999,
            'page_type'   => 'home',
            'status'      => 'published',
            'sort_order'  => 0,
        ]);
        $homePageId = 999;

        $this->db->table('cms_page_translations')->insert([
            'page_id'     => $homePageId,
            'language_id' => $this->langEsId,
            'title'       => 'Home',
            'slug'        => 'home',
        ]);

        $item = new MenuItemEntity([
            'id'        => 2,
            'link_type' => 'page',
            'page_id'   => $homePageId,
        ]);

        $url = $this->service->resolveLink($item, 'es');

        $this->assertSame('/', $url);
    }

    public function testResolveLinkForCollection(): void
    {
        $item = new MenuItemEntity([
            'id'            => 3,
            'link_type'     => 'collection_listing',
            'collection_id' => $this->collectionId,
        ]);

        $url = $this->service->resolveLink($item, 'es');

        $this->assertSame('/blog', $url);
    }

    public function testResolveLinkForEntry(): void
    {
        $item = new MenuItemEntity([
            'id'       => 4,
            'link_type' => 'entry',
            'entry_id' => $this->entryId,
        ]);

        $url = $this->service->resolveLink($item, 'es');

        $this->assertSame('/blog/first-post', $url);
    }

    public function testResolveLinkReturnsNullIfPageNotFound(): void
    {
        $item = new MenuItemEntity([
            'id'        => 5,
            'link_type' => 'page',
            'page_id'   => 99999,
        ]);

        $url = $this->service->resolveLink($item, 'es');

        $this->assertNull($url);
    }

    public function testResolveLinkReturnsNullIfEntryNotFound(): void
    {
        $item = new MenuItemEntity([
            'id'        => 6,
            'link_type' => 'entry',
            'entry_id'  => 99999,
        ]);

        $url = $this->service->resolveLink($item, 'es');

        $this->assertNull($url);
    }

    public function testResolveLinkReturnsNullForCustomUrl(): void
    {
        $item = new MenuItemEntity([
            'id'        => 7,
            'link_type' => 'custom_url',
        ]);

        $url = $this->service->resolveLink($item, 'es');

        $this->assertNull($url);
    }

    public function testResolveLinkReturnsNullForNoLink(): void
    {
        $item = new MenuItemEntity([
            'id'        => 8,
            'link_type' => 'no_link',
        ]);

        $url = $this->service->resolveLink($item, 'es');

        $this->assertNull($url);
    }

    public function testResolveLinkForCollectionWithIndexPage(): void
    {
        // Create an index page for the collection
        $this->db->table('cms_pages')->insert([
            'page_type'     => 'collection_index',
            'collection_id' => $this->collectionId,
            'status'        => 'published',
            'sort_order'    => 1,
        ]);
        $pageId = $this->db->insertID();

        // Add translation with a custom slug
        $this->db->table('cms_page_translations')->insert([
            'page_id'     => $pageId,
            'language_id' => $this->langEsId,
            'title'       => 'Artículos',
            'slug'        => 'articulos-custom',
        ]);

        $item = new MenuItemEntity([
            'id'            => 100,
            'link_type'     => 'collection_listing',
            'collection_id' => $this->collectionId,
        ]);

        $url = $this->service->resolveLink($item, 'es');

        // Should resolve to the custom slug of the index page, not 'blog' (the collection slug)
        $this->assertSame('/articulos-custom', $url);
    }

    public function testResolveLinkForEntryWithIndexPage(): void
    {
        // Create an index page for the collection
        $this->db->table('cms_pages')->insert([
            'page_type'     => 'collection_index',
            'collection_id' => $this->collectionId,
            'status'        => 'published',
            'sort_order'    => 1,
        ]);
        $pageId = $this->db->insertID();

        // Add translation with a custom slug
        $this->db->table('cms_page_translations')->insert([
            'page_id'     => $pageId,
            'language_id' => $this->langEsId,
            'title'       => 'Artículos',
            'slug'        => 'articulos-custom',
        ]);

        $item = new MenuItemEntity([
            'id'       => 101,
            'link_type' => 'entry',
            'entry_id' => $this->entryId,
        ]);

        $url = $this->service->resolveLink($item, 'es');

        // Should resolve to /articulos-custom/first-post, not /blog/first-post
        $this->assertSame('/articulos-custom/first-post', $url);
    }

    public function testDestroyInvalidatesCache(): void
    {
        $repository = $this->createMock(RepositoryInterface::class);
        $repository->expects($this->once())
            ->method('find')
            ->with(10)
            ->willReturn((object) ['id' => 10]);
        $repository->expects($this->once())
            ->method('setEntityContext')
            ->with(10, $this->isInstanceOf(\stdClass::class));
        $repository->expects($this->once())
            ->method('delete')
            ->with(10)
            ->willReturn(true);

        $responseMapper = $this->createMock(ResponseMapperInterface::class);
        $cacheMock = $this->createMock(\App\Libraries\Cms\CacheInvalidationClient::class);
        $cacheMock->expects($this->once())
            ->method('invalidate')
            ->with(['menus']);

        $service = new \App\Services\Cms\MenuItemService(
            $repository,
            $responseMapper,
            $cacheMock,
            $this->createMock(\App\Libraries\Cms\TranslationResolver::class),
            $this->createMock(\App\Libraries\Cms\SlugRouter::class)
        );
        $result = $service->destroy(10, null);

        $this->assertTrue($result);
    }
}
