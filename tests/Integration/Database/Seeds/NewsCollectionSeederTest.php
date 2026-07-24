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
            'cms_file_references',
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

        // Regression guard for the 2026-07-22 bug: the collection's own
        // block_template previously kept page_header/hero_banner/cta/alert
        // (landing-page blocks) even though the seeded sample entries below
        // were always pruned down to just rich_text+image — causing the
        // Wizard to ask for 4 irrelevant blocks on every new noticia.
        $blockTemplate = json_decode((string) $collection['block_template'], true);
        $this->assertIsArray($blockTemplate);
        $this->assertSame(
            ['rich_text', 'image'],
            array_column($blockTemplate['blocks'], 'block_key')
        );

        $entries = $this->db->table('cms_entries')
            ->where('collection_id', (int) $collection['id'])
            ->orderBy('sort_order', 'ASC')
            ->get()
            ->getResultArray();

        $this->assertCount(3, $entries);

        $entryTranslations = $this->db->table('cms_entry_translations')
            ->where('entry_id', (int) $entries[0]['id'])
            ->get()
            ->getResultArray();

        $this->assertNotEmpty($entryTranslations);
        $this->assertNotEmpty($entryTranslations[0]['featured_image_url'] ?? null);

        $blockInstances = $this->db->table('cms_block_instances')
            ->where('owner_type', 'entry')
            ->where('owner_id', (int) $entries[0]['id'])
            ->orderBy('sort_order', 'ASC')
            ->get()
            ->getResultArray();

        $this->assertCount(2, $blockInstances);
        $this->assertSame(
            ['image', 'rich_text'],
            array_map(function (array $block): string {
                $type = $this->db->table('cms_content_blocks')
                    ->where('id', (int) $block['block_id'])
                    ->get()
                    ->getRowArray();

                return (string) ($type['block_key'] ?? '');
            }, $blockInstances)
        );

        $this->assertSame(4, $this->db->table('cms_block_instance_translations')
            ->whereIn('instance_id', array_column($blockInstances, 'id'))
            ->countAllResults());
    }
}
