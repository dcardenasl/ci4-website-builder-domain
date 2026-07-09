<?php

declare(strict_types=1);

namespace Tests\Integration\Database\Seeds;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class SiteAboutPageSeederTest extends CIUnitTestCase
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
            'cms_page_translations',
            'cms_pages',
            'cms_languages',
        ];

        foreach ($tables as $table) {
            $this->db->query("DELETE FROM `{$table}`");
        }
        $this->db->enableForeignKeyChecks();
    }

    public function testAboutPageSeederSeedsAReusableGalleryBlock(): void
    {
        $seeder = \Config\Database::seeder();
        $seeder->call(\App\Database\Seeds\CmsLanguageSeeder::class);
        $seeder->call(\App\Database\Seeds\CmsBlockTypeSeeder::class);
        $seeder->call(\App\Database\Seeds\SiteAboutPageSeeder::class);

        $aboutPage = $this->db->table('cms_pages')
            ->where('page_type', 'about')
            ->get()
            ->getRowArray();

        $this->assertNotNull($aboutPage);

        $galleryBlock = $this->db->table('cms_block_instances')
            ->where('owner_type', 'page')
            ->where('owner_id', (int) $aboutPage['id'])
            ->where('parent_instance_id IS NULL', null, false)
            ->where('sort_order', 8)
            ->get()
            ->getRowArray();

        $this->assertNotNull($galleryBlock);
        $this->assertSame('gallery', $this->blockKeyForInstance((int) $galleryBlock['block_id']));

        $config = json_decode((string) ($galleryBlock['block_config'] ?? '{}'), true);
        $this->assertIsArray($config);
        $this->assertSame('modal_preview', $config['presentation_mode'] ?? null);
        $this->assertSame('3', (string) ($config['columns'] ?? ''));

        $galleryChildren = $this->db->table('cms_block_instances')
            ->where('parent_instance_id', (int) $galleryBlock['id'])
            ->orderBy('sort_order', 'ASC')
            ->get()
            ->getResultArray();

        $this->assertCount(3, $galleryChildren);
        $this->assertSame('gallery_item', $this->blockKeyForInstance((int) $galleryChildren[0]['block_id']));
    }

    private function blockKeyForInstance(int $blockId): string
    {
        $row = $this->db->table('cms_content_blocks')
            ->where('id', $blockId)
            ->get()
            ->getRowArray();

        return (string) ($row['block_key'] ?? '');
    }
}
