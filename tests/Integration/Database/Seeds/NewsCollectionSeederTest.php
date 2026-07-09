<?php

declare(strict_types=1);

namespace Tests\Integration\Database\Seeds;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class NewsCollectionSeederTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = false;
    protected $namespace   = 'App';

    protected function setUp(): void
    {
        parent::setUp();

        $this->db->disableForeignKeyChecks();
        $tables = [
            'cms_block_instance_translations',
            'cms_block_instances',
            'cms_content_blocks',
            'cms_entry_translations',
            'cms_entries',
            'cms_page_translations',
            'cms_pages',
            'cms_collection_translations',
            'cms_collections',
            'cms_languages',
        ];

        foreach ($tables as $table) {
            $this->db->query("DELETE FROM `{$table}`");
        }
        $this->db->enableForeignKeyChecks();
    }

    public function testNewsSeederCreatesEntriesWithFeaturedImages(): void
    {
        $seeder = \Config\Database::seeder();
        $seeder->call(\App\Database\Seeds\CmsLanguageSeeder::class);
        $seeder->call(\App\Database\Seeds\CmsBlockTypeSeeder::class);
        $seeder->call(\App\Database\Seeds\NewsCollectionSeeder::class);

        $collection = $this->db->table('cms_collections')
            ->where('collection_key', 'noticias')
            ->get()
            ->getRowArray();

        $this->assertNotNull($collection);

        $entries = $this->db->table('cms_entries')
            ->where('collection_id', (int) $collection['id'])
            ->orderBy('sort_order', 'ASC')
            ->get()
            ->getResultArray();

        $this->assertCount(2, $entries);

        $entryTranslations = $this->db->table('cms_entry_translations')
            ->where('entry_id', (int) $entries[0]['id'])
            ->get()
            ->getResultArray();

        $this->assertNotEmpty($entryTranslations);
        $this->assertNotEmpty($entryTranslations[0]['featured_image_url'] ?? null);
    }
}
