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
            'cms_file_references',
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
            ->select('cms_pages.id')
            ->join('cms_page_translations', 'cms_page_translations.page_id = cms_pages.id')
            ->join('cms_languages', 'cms_languages.id = cms_page_translations.language_id')
            ->whereIn('cms_page_translations.slug', ['nosotros', 'about'])
            ->where('cms_languages.code', 'es')
            ->get()
            ->getRowArray();

        $this->assertNotNull($aboutPage);

        $galleryBlock = $this->db->table('cms_block_instances')
            ->select('cms_block_instances.*')
            ->join('cms_content_blocks', 'cms_content_blocks.id = cms_block_instances.block_id')
            ->where('owner_type', 'page')
            ->where('owner_id', (int) $aboutPage['id'])
            ->where('cms_content_blocks.block_key', 'gallery')
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
        $galleryItemConfig = json_decode((string) ($galleryChildren[0]['block_config'] ?? '{}'), true);
        $this->assertIsArray($galleryItemConfig);
        $this->assertSame('external_url', $galleryItemConfig['image']['source_kind'] ?? null);
    }

    public function testAboutPageSeederSeedsTeamMemberPhotosWithTheCurrentMediaReferenceContract(): void
    {
        $seeder = \Config\Database::seeder();
        $seeder->call(\App\Database\Seeds\CmsLanguageSeeder::class);
        $seeder->call(\App\Database\Seeds\CmsBlockTypeSeeder::class);
        $seeder->call(\App\Database\Seeds\SiteAboutPageSeeder::class);

        $aboutPage = $this->db->table('cms_pages')
            ->select('cms_pages.id')
            ->join('cms_page_translations', 'cms_page_translations.page_id = cms_pages.id')
            ->join('cms_languages', 'cms_languages.id = cms_page_translations.language_id')
            ->whereIn('cms_page_translations.slug', ['nosotros', 'about'])
            ->where('cms_languages.code', 'es')
            ->get()
            ->getRowArray();

        $this->assertNotNull($aboutPage);

        $teamGrid = $this->db->table('cms_block_instances')
            ->select('cms_block_instances.*')
            ->join('cms_content_blocks', 'cms_content_blocks.id = cms_block_instances.block_id')
            ->where('owner_type', 'page')
            ->where('owner_id', (int) $aboutPage['id'])
            ->where('cms_content_blocks.block_key', 'team_grid')
            ->get()
            ->getRowArray();

        $this->assertNotNull($teamGrid);

        $teamMember = $this->db->table('cms_block_instances')
            ->where('parent_instance_id', (int) $teamGrid['id'])
            ->orderBy('sort_order', 'ASC')
            ->get()
            ->getRowArray();

        $this->assertNotNull($teamMember);
        $this->assertSame('team_member', $this->blockKeyForInstance((int) $teamMember['block_id']));

        $blockConfig = json_decode((string) ($teamMember['block_config'] ?? '{}'), true);
        $this->assertIsArray($blockConfig);
        $this->assertArrayHasKey('photo', $blockConfig);
        $this->assertSame('external_url', $blockConfig['photo']['source_kind'] ?? null);
        $this->assertSame('https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=150&auto=format&fit=crop&q=60', $blockConfig['photo']['url'] ?? null);
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
