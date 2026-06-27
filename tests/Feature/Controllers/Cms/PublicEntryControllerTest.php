<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Cms;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class PublicEntryControllerTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    private int $langEsId;
    private int $collectionId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db->disableForeignKeyChecks();
        $this->db->table('cms_entry_translations')->truncate();
        $this->db->table('cms_entries')->truncate();
        $this->db->table('cms_collections')->truncate();
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

        // Seed collection
        $this->db->table('cms_collections')->insert([
            'collection_key' => 'blog',
            'is_active' => 1,
            'requires_approval' => 0,
            'enables_categories' => 1,
            'enables_tags' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $this->collectionId = $this->db->insertID();
    }

    public function testGetPublicEntriesSuccess(): void
    {
        $this->db->table('cms_entries')->insert([
            'collection_id' => $this->collectionId,
            'workflow_status' => 'published',
            'is_featured' => 1,
            'view_count' => 10,
            'sort_order' => 1,
            'is_in_sitemap' => 1,
        ]);
        $entryId = $this->db->insertID();

        $this->db->table('cms_entry_translations')->insert([
            'entry_id'     => $entryId,
            'language_id'        => $this->langEsId,
            'slug'               => 'primer-post',
            'title'              => 'Primer Post',
            'excerpt'            => 'Esta es la primera entrada de blog.',
            'featured_image_url' => 'http://localhost:8180/uploads/posts/primer-post.png',
        ]);

        $result = $this->get('/api/v1/public/es/entries/blog');

        $result->assertStatus(200);

        $body = json_decode($result->getJSON(), true);
        $this->assertSame('success', $body['status']);
        $this->assertCount(1, $body['data']);
        $this->assertSame('primer-post', $body['data'][0]['slug']);
        $this->assertSame('Primer Post', $body['data'][0]['title']);
    }

    public function testGetPublicEntryDetailSuccess(): void
    {
        $this->db->table('cms_entries')->insert([
            'collection_id' => $this->collectionId,
            'workflow_status' => 'published',
            'is_featured' => 1,
            'view_count' => 10,
            'sort_order' => 1,
            'is_in_sitemap' => 1,
        ]);
        $entryId = $this->db->insertID();

        $this->db->table('cms_entry_translations')->insert([
            'entry_id'     => $entryId,
            'language_id'        => $this->langEsId,
            'slug'               => 'primer-post',
            'title'              => 'Primer Post',
            'excerpt'            => 'Esta es la primera entrada de blog.',
            'featured_image_url' => 'http://localhost:8180/uploads/posts/primer-post.png',
        ]);

        $result = $this->get('/api/v1/public/es/entries/blog/primer-post');

        $result->assertStatus(200);

        $body = json_decode($result->getJSON(), true);
        $this->assertSame('success', $body['status']);
        $this->assertSame('primer-post', $body['data']['slug']);
        $this->assertSame('Primer Post', $body['data']['title']);
        $this->assertIsArray($body['data']['blocks']);
    }

    public function testGetPublicEntryNotFound(): void
    {
        $result = $this->get('/api/v1/public/es/entries/blog/no-existe');
        $result->assertStatus(404);
    }

    public function testListingIncludesFeaturedImageUrl(): void
    {
        // Create entry with a public featured image URL
        $this->db->table('cms_entries')->insert([
            'collection_id' => $this->collectionId,
            'workflow_status' => 'published',
            'is_featured' => 1,
            'view_count' => 10,
            'sort_order' => 1,
            'is_in_sitemap' => 1,
        ]);
        $entryId = $this->db->insertID();

        $this->db->table('cms_entry_translations')->insert([
            'entry_id'           => $entryId,
            'language_id'        => $this->langEsId,
            'slug'               => 'post-con-imagen',
            'title'              => 'Post con Imagen',
            'excerpt'            => 'Post con imagen destacada',
            'featured_file_id'   => 42,
            'featured_image_url' => 'http://localhost:8180/uploads/posts/post-con-imagen.png',
        ]);

        $result = $this->get('/api/v1/public/es/entries/blog');

        $result->assertStatus(200);

        $body = json_decode($result->getJSON(), true);
        $this->assertSame('success', $body['status']);
        $this->assertCount(1, $body['data']);
        $this->assertArrayHasKey('featured_image_url', $body['data'][0]);
        $this->assertSame('http://localhost:8180/uploads/posts/post-con-imagen.png', $body['data'][0]['featured_image_url']);
    }

    public function testShowIncludesFeaturedImageUrl(): void
    {
        // Create entry with a public featured image URL
        $this->db->table('cms_entries')->insert([
            'collection_id' => $this->collectionId,
            'workflow_status' => 'published',
            'is_featured' => 1,
            'view_count' => 10,
            'sort_order' => 1,
            'is_in_sitemap' => 1,
        ]);
        $entryId = $this->db->insertID();

        $this->db->table('cms_entry_translations')->insert([
            'entry_id'           => $entryId,
            'language_id'        => $this->langEsId,
            'slug'               => 'detalle-con-imagen',
            'title'              => 'Detalle con Imagen',
            'excerpt'            => 'Detalle con imagen destacada',
            'featured_file_id'   => 99,
            'featured_image_url' => 'http://localhost:8180/uploads/posts/detalle-con-imagen.png',
        ]);

        $result = $this->get('/api/v1/public/es/entries/blog/detalle-con-imagen');

        $result->assertStatus(200);

        $body = json_decode($result->getJSON(), true);
        $this->assertSame('success', $body['status']);
        $this->assertArrayHasKey('featured_image_url', $body['data']);
        $this->assertSame('http://localhost:8180/uploads/posts/detalle-con-imagen.png', $body['data']['featured_image_url']);
    }

    public function testGetPublicEntriesFilteredByCategoryAndTag(): void
    {
        // Truncate taxonomy tables
        $this->db->disableForeignKeyChecks();
        $this->db->table('cms_entry_categories')->truncate();
        $this->db->table('cms_entry_tags')->truncate();
        $this->db->table('cms_category_translations')->truncate();
        $this->db->table('cms_categories')->truncate();
        $this->db->table('cms_tag_translations')->truncate();
        $this->db->table('cms_tags')->truncate();
        $this->db->enableForeignKeyChecks();

        // 1. Setup category
        $this->db->table('cms_categories')->insert([
            'collection_id' => $this->collectionId,
            'sort_order'    => 1,
            'is_active'     => 1,
        ]);
        $categoryId = $this->db->insertID();
        $this->db->table('cms_category_translations')->insert([
            'category_id' => $categoryId,
            'language_id' => $this->langEsId,
            'slug'        => 'noticias',
            'name'        => 'Noticias',
        ]);

        // 2. Setup tag
        $this->db->table('cms_tags')->insert([
            'is_active' => 1,
        ]);
        $tagId = $this->db->insertID();
        $this->db->table('cms_tag_translations')->insert([
            'tag_id'      => $tagId,
            'language_id' => $this->langEsId,
            'slug'        => 'php',
            'name'        => 'PHP',
        ]);

        // 3. Create entry
        $this->db->table('cms_entries')->insert([
            'collection_id'   => $this->collectionId,
            'workflow_status' => 'published',
            'is_featured'     => 1,
            'view_count'      => 10,
            'sort_order'      => 1,
            'is_in_sitemap'   => 1,
        ]);
        $entryId = $this->db->insertID();
        $this->db->table('cms_entry_translations')->insert([
            'entry_id'    => $entryId,
            'language_id' => $this->langEsId,
            'slug'        => 'post-filtrado',
            'title'       => 'Post Filtrado',
            'excerpt'     => 'Texto de prueba',
        ]);

        // 4. Link category and tag
        $this->db->table('cms_entry_categories')->insert([
            'entry_id'    => $entryId,
            'category_id' => $categoryId,
            'sort_order'  => 0,
        ]);
        $this->db->table('cms_entry_tags')->insert([
            'entry_id' => $entryId,
            'tag_id'   => $tagId,
        ]);

        // 5. Query without filters -> should return it and include categories & tags
        $result = $this->get('/api/v1/public/es/entries/blog');
        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);
        $this->assertCount(1, $body['data']);
        $this->assertSame('noticias', $body['data'][0]['categories'][0]['slug']);
        $this->assertSame('php', $body['data'][0]['tags'][0]['slug']);

        // 6. Query with correct category filter -> should return it
        $result = $this->get('/api/v1/public/es/entries/blog?category=noticias');
        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);
        $this->assertCount(1, $body['data']);

        // 7. Query with incorrect category filter -> should return empty
        $result = $this->get('/api/v1/public/es/entries/blog?category=deportes');
        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);
        $this->assertCount(0, $body['data']);

        // 8. Query with correct tag filter -> should return it
        $result = $this->get('/api/v1/public/es/entries/blog?tag=php');
        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);
        $this->assertCount(1, $body['data']);

        // 9. Query with incorrect tag filter -> should return empty
        $result = $this->get('/api/v1/public/es/entries/blog?tag=java');
        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);
        $this->assertCount(0, $body['data']);
    }
}
