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
            'language_id' => $this->langEsId,
            'slug'        => 'primer-post',
            'title'       => 'Primer Post',
            'excerpt'     => 'Esta es la primera entrada de blog.',
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
            'language_id' => $this->langEsId,
            'slug'        => 'primer-post',
            'title'       => 'Primer Post',
            'excerpt'     => 'Esta es la primera entrada de blog.',
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
}
