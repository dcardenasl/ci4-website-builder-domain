<?php

declare(strict_types=1);

namespace Tests\Integration\Database\Seeds;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class PortfolioCollectionSeederTest extends CIUnitTestCase
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
            'cms_entry_tags',
            'cms_entry_categories',
            'cms_entry_translations',
            'cms_entries',
            'cms_tag_translations',
            'cms_tags',
            'cms_category_translations',
            'cms_categories',
            'cms_collection_translations',
            'cms_collections',
            'cms_languages',
        ];

        foreach ($tables as $table) {
            $this->db->query("DELETE FROM `{$table}`");
        }
        $this->db->enableForeignKeyChecks();
    }

    public function testPortfolioSeederCreatesEntriesWithoutLandingPageBlocks(): void
    {
        $seeder = \Config\Database::seeder();
        $seeder->call(\App\Database\Seeds\CmsLanguageSeeder::class);
        $seeder->call(\App\Database\Seeds\CmsBlockTypeSeeder::class);
        $seeder->call(\App\Database\Seeds\PortfolioCollectionSeeder::class);

        $collection = $this->db->table('cms_collections')
            ->where('collection_key', 'portafolio')
            ->get()
            ->getRowArray();

        $this->assertNotNull($collection);

        // Regression guard for the 2026-07-22 bug: the collection's own
        // block_template previously kept page_header/hero_banner/cta/alert
        // even though the seeded sample entries below were always pruned
        // down to just image+rich_text — causing the Wizard to ask for 4
        // irrelevant blocks on every new portfolio entry.
        $blockTemplate = json_decode((string) $collection['block_template'], true);
        $this->assertIsArray($blockTemplate);
        $this->assertSame(
            ['image', 'rich_text'],
            array_column($blockTemplate['blocks'], 'block_key')
        );

        $entries = $this->db->table('cms_entries')
            ->where('collection_id', (int) $collection['id'])
            ->get()
            ->getResultArray();

        $this->assertCount(2, $entries);

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
    }
}
