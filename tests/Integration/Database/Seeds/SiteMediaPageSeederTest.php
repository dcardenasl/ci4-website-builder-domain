<?php

declare(strict_types=1);

namespace Tests\Integration\Database\Seeds;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class SiteMediaPageSeederTest extends CIUnitTestCase
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

    public function testMediaPageSeederCreatesAllGalleryPresentationModes(): void
    {
        $seeder = \Config\Database::seeder();
        $seeder->call(\App\Database\Seeds\CmsLanguageSeeder::class);
        $seeder->call(\App\Database\Seeds\CmsBlockTypeSeeder::class);
        $seeder->call(\App\Database\Seeds\SiteMediaPageSeeder::class);

        $page = $this->db->table('cms_pages')
            ->select('cms_pages.*')
            ->join('cms_page_translations', 'cms_page_translations.page_id = cms_pages.id')
            ->where('cms_page_translations.slug', 'multimedia')
            ->get()
            ->getRowArray();

        $this->assertNotNull($page);

        $blocks = $this->db->table('cms_block_instances')
            ->select('cms_content_blocks.block_key, cms_block_instances.id, cms_block_instances.sort_order, cms_block_instances.block_config')
            ->join('cms_content_blocks', 'cms_content_blocks.id = cms_block_instances.block_id')
            ->where('cms_block_instances.owner_type', 'page')
            ->where('cms_block_instances.owner_id', (int) $page['id'])
            ->where('cms_block_instances.parent_instance_id IS NULL', null, false)
            ->orderBy('cms_block_instances.sort_order', 'ASC')
            ->get()
            ->getResultArray();

        $this->assertSame(['page_header', 'rich_text', 'alert', 'gallery', 'rich_text', 'gallery', 'rich_text', 'gallery', 'video_player', 'document_gallery', 'pdf_viewer', 'external_links', 'cta'], array_column($blocks, 'block_key'));

        $gridGallery = $this->galleryBySortOrder((int) $page['id'], 4);
        $inlineGallery = $this->galleryBySortOrder((int) $page['id'], 6);
        $modalGallery = $this->galleryBySortOrder((int) $page['id'], 8);

        $this->assertSame('grid', $gridGallery['mode']);
        $this->assertSame('inline_preview', $inlineGallery['mode']);
        $this->assertSame('modal_preview', $modalGallery['mode']);

        $videoBlock = $this->db->table('cms_block_instances')
            ->select('cms_block_instances.id, cms_block_instances.block_config')
            ->join('cms_content_blocks', 'cms_content_blocks.id = cms_block_instances.block_id')
            ->where('cms_block_instances.owner_type', 'page')
            ->where('cms_block_instances.owner_id', (int) $page['id'])
            ->where('cms_block_instances.parent_instance_id IS NULL', null, false)
            ->where('cms_content_blocks.block_key', 'video_player')
            ->get()
            ->getRowArray();

        $this->assertNotNull($videoBlock);

        $videoConfig = json_decode((string) ($videoBlock['block_config'] ?? '{}'), true);
        $this->assertIsArray($videoConfig);
        $this->assertSame('16/9', $videoConfig['aspect_ratio'] ?? null);

        $documentBlock = $this->db->table('cms_block_instances')
            ->select('cms_block_instance_translations.block_data')
            ->join('cms_content_blocks', 'cms_content_blocks.id = cms_block_instances.block_id')
            ->join('cms_block_instance_translations', 'cms_block_instance_translations.instance_id = cms_block_instances.id')
            ->join('cms_languages', 'cms_languages.id = cms_block_instance_translations.language_id')
            ->where('cms_block_instances.owner_type', 'page')
            ->where('cms_block_instances.owner_id', (int) $page['id'])
            ->where('cms_block_instances.parent_instance_id IS NULL', null, false)
            ->where('cms_content_blocks.block_key', 'document_gallery')
            ->where('cms_languages.code', 'es')
            ->get()
            ->getRowArray();

        $this->assertNotNull($documentBlock);

        $documentData = json_decode((string) ($documentBlock['block_data'] ?? '{}'), true);
        $this->assertIsArray($documentData);
        $documents = $documentData['documents'] ?? [];
        $this->assertIsArray($documents);
        $this->assertSame(
            'http://localhost:8186/assets/docs/policies-handbook-demo.pdf',
            $documents[0]['file']['url'] ?? null
        );

        $pdfBlock = $this->db->table('cms_block_instances')
            ->select('cms_block_instances.block_config')
            ->join('cms_content_blocks', 'cms_content_blocks.id = cms_block_instances.block_id')
            ->where('cms_block_instances.owner_type', 'page')
            ->where('cms_block_instances.owner_id', (int) $page['id'])
            ->where('cms_block_instances.parent_instance_id IS NULL', null, false)
            ->where('cms_content_blocks.block_key', 'pdf_viewer')
            ->get()
            ->getRowArray();

        $this->assertNotNull($pdfBlock);

        $pdfConfig = json_decode((string) ($pdfBlock['block_config'] ?? '{}'), true);
        $this->assertIsArray($pdfConfig);
        $this->assertSame(
            'http://localhost:8186/assets/docs/policies-handbook-demo.pdf',
            $pdfConfig['pdf_file']['url'] ?? null
        );
    }

    /**
     * @return array{mode:string}
     */
    private function galleryBySortOrder(int $pageId, int $sortOrder): array
    {
        $row = $this->db->table('cms_block_instances')
            ->select('cms_block_instances.block_config')
            ->join('cms_content_blocks', 'cms_content_blocks.id = cms_block_instances.block_id')
            ->where('cms_block_instances.owner_type', 'page')
            ->where('cms_block_instances.owner_id', $pageId)
            ->where('cms_block_instances.parent_instance_id IS NULL', null, false)
            ->where('cms_content_blocks.block_key', 'gallery')
            ->where('cms_block_instances.sort_order', $sortOrder)
            ->get()
            ->getRowArray();

        $this->assertNotNull($row);

        $config = json_decode((string) ($row['block_config'] ?? '{}'), true);
        $this->assertIsArray($config);

        return ['mode' => (string) ($config['presentation_mode'] ?? '')];
    }
}
