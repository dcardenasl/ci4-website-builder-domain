<?php

declare(strict_types=1);

namespace Tests\Unit\Libraries\Cms;

use App\Libraries\Cms\SlugRouter;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class SlugRouterTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    private int $langEsId;
    private int $langEnId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db->disableForeignKeyChecks();
        $this->db->query("DELETE FROM `cms_page_translations`");
        $this->db->query("DELETE FROM `cms_pages`");
        $this->db->query("DELETE FROM `cms_languages`");
        $this->db->enableForeignKeyChecks();

        // Seed languages
        $this->db->table('cms_languages')->insert([
            'code'       => 'es',
            'name'       => 'Spanish',
            'is_default' => 1,
            'is_active'  => 1,
        ]);
        $this->langEsId = $this->db->insertID();

        $this->db->table('cms_languages')->insert([
            'code'       => 'en',
            'name'       => 'English',
            'is_default' => 0,
            'is_active'  => 1,
        ]);
        $this->langEnId = $this->db->insertID();
    }

    public function testResolveSimpleSlug(): void
    {
        $this->db->table('cms_pages')->insert([
            'status' => 'published',
        ]);
        $pageId = $this->db->insertID();

        $this->db->table('cms_page_translations')->insert([
            'page_id'     => $pageId,
            'language_id' => $this->langEsId,
            'slug'        => 'nosotros',
            'title'       => 'Nosotros',
        ]);

        $router = new SlugRouter($this->db);
        $resolved = $router->resolve('es', 'page', 'nosotros');

        $this->assertSame($pageId, $resolved);
    }

    public function testResolveNestedSlug(): void
    {
        $this->db->table('cms_pages')->insert([
            'status' => 'published',
        ]);
        $parentPageId = $this->db->insertID();

        $this->db->table('cms_page_translations')->insert([
            'page_id'     => $parentPageId,
            'language_id' => $this->langEsId,
            'slug'        => 'nosotros',
            'title'       => 'Nosotros',
        ]);

        $this->db->table('cms_pages')->insert([
            'parent_id' => $parentPageId,
            'status'    => 'published',
        ]);
        $childPageId = $this->db->insertID();

        $this->db->table('cms_page_translations')->insert([
            'page_id'     => $childPageId,
            'language_id' => $this->langEsId,
            'slug'        => 'vision',
            'title'       => 'Vision',
        ]);

        $router = new SlugRouter($this->db);
        $resolved = $router->resolve('es', 'page', 'nosotros/vision');

        $this->assertSame($childPageId, $resolved);
    }

    public function testResolvePublishedWithFuturePublishedAtReturnsNull(): void
    {
        // Regression: `SlugRouter` used to gate on `status` alone, so a page
        // marked published with a `published_at` still in the future stayed
        // reachable by direct URL. `ScheduledPublishingJob` writes that column,
        // but no access-control path read it.
        $this->db->table('cms_pages')->insert([
            'status'       => 'published',
            'published_at' => date('Y-m-d H:i:s', time() + 86400),
        ]);
        $pageId = $this->db->insertID();

        $this->db->table('cms_page_translations')->insert([
            'page_id'     => $pageId,
            'language_id' => $this->langEsId,
            'slug'        => 'futura',
            'title'       => 'Futura',
        ]);

        $router = new SlugRouter($this->db);

        $this->assertNull($router->resolve('es', 'page', 'futura'));
        $this->assertSame($pageId, $router->resolve('es', 'page', 'futura', true));
    }

    public function testResolvePublishedWithFutureScheduledAtReturnsNull(): void
    {
        $this->db->table('cms_pages')->insert([
            'status'       => 'published',
            'scheduled_at' => date('Y-m-d H:i:s', time() + 86400),
        ]);
        $pageId = $this->db->insertID();

        $this->db->table('cms_page_translations')->insert([
            'page_id'     => $pageId,
            'language_id' => $this->langEsId,
            'slug'        => 'programada',
            'title'       => 'Programada',
        ]);

        $router = new SlugRouter($this->db);

        $this->assertNull($router->resolve('es', 'page', 'programada'));
    }

    public function testResolvePublishedWithPastPublishedAtResolves(): void
    {
        $this->db->table('cms_pages')->insert([
            'status'       => 'published',
            'published_at' => date('Y-m-d H:i:s', time() - 86400),
        ]);
        $pageId = $this->db->insertID();

        $this->db->table('cms_page_translations')->insert([
            'page_id'     => $pageId,
            'language_id' => $this->langEsId,
            'slug'        => 'vigente',
            'title'       => 'Vigente',
        ]);

        $router = new SlugRouter($this->db);

        $this->assertSame($pageId, $router->resolve('es', 'page', 'vigente'));
    }

    public function testResolveHomeWithFuturePublishedAtReturnsNull(): void
    {
        // The home branch of resolve() is a separate query from the slug walk;
        // it had the same defect.
        $this->db->table('cms_pages')->insert([
            'page_type'    => 'home',
            'status'       => 'published',
            'published_at' => date('Y-m-d H:i:s', time() + 86400),
        ]);

        $router = new SlugRouter($this->db);

        $this->assertNull($router->resolve('es', 'page', ''));
    }

    public function testResolveDraftReturnsNull(): void
    {
        $this->db->table('cms_pages')->insert([
            'status' => 'draft',
        ]);
        $pageId = $this->db->insertID();

        $this->db->table('cms_page_translations')->insert([
            'page_id'     => $pageId,
            'language_id' => $this->langEsId,
            'slug'        => 'nosotros',
            'title'       => 'Nosotros',
        ]);

        $router = new SlugRouter($this->db);
        $resolved = $router->resolve('es', 'page', 'nosotros');

        $this->assertNull($resolved);
    }
}
