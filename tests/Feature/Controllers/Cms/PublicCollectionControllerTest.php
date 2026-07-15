<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Cms;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class PublicCollectionControllerTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = true;
    protected $namespace   = 'App';

    private int $langEsId;
    private int $langEnId;
    private int $collectionId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db->disableForeignKeyChecks();
        $this->db->query("DELETE FROM `cms_collection_translations`");
        $this->db->query("DELETE FROM `cms_collections`");
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

        $this->db->table('cms_languages')->insert([
            'code'       => 'en',
            'name'       => 'English',
            'is_default' => 0,
            'is_active'  => 1,
        ]);
        $this->langEnId = $this->db->insertID();

        // Seed collection
        $this->db->table('cms_collections')->insert([
            'collection_key'      => 'blog',
            'is_active'           => 1,
            'requires_approval'   => 0,
            'enables_categories'  => 1,
            'enables_tags'        => 1,
            'sort_order'          => 1,
        ]);
        $this->collectionId = $this->db->insertID();

        $this->db->table('cms_collection_translations')->insert([
            'collection_id' => $this->collectionId,
            'language_id'   => $this->langEsId,
            'slug'          => 'blog',
            'name'          => 'Mi Blog',
            'description'   => 'El blog principal de noticias.',
        ]);

        $this->db->table('cms_collection_translations')->insert([
            'collection_id' => $this->collectionId,
            'language_id'   => $this->langEnId,
            'slug'          => 'news',
            'name'          => 'My Blog',
            'description'   => 'The main news blog.',
        ]);
    }

    public function testGetPublicCollectionsSuccess(): void
    {
        $result = $this->get('/api/v1/public/es/collections');

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);

        $this->assertSame('success', $body['status']);
        $this->assertCount(1, $body['data']);
        $this->assertSame('blog', $body['data'][0]['collection_key']);
        $this->assertSame('blog', $body['data'][0]['slug']);
        $this->assertSame('Mi Blog', $body['data'][0]['name']);
        $this->assertSame('El blog principal de noticias.', $body['data'][0]['description']);
        $this->assertSame('blog', $body['data'][0]['localized_slugs']['es']);
        $this->assertSame('news', $body['data'][0]['localized_slugs']['en']);
        $this->assertArrayNotHasKey('url_prefix', $body['data'][0]);
    }

    public function testGetPublicCollectionsFallsBackToNameWhenListingTitleIsEmpty(): void
    {
        $this->db->table('cms_collection_translations')
            ->where('collection_id', $this->collectionId)
            ->where('language_id', $this->langEsId)
            ->update([
                'slug'          => 'festivales',
                'name'          => 'Festivales',
                'listing_title' => '',
            ]);

        $result = $this->get('/api/v1/public/es/collections');

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);

        $this->assertSame('Festivales', $body['data'][0]['listing_title']);
    }
}
