<?php

declare(strict_types=1);

namespace Tests\Integration\Services\Cms;

use App\Database\Seeds\CmsBlockTypeSeeder;
use App\Database\Seeds\CmsLanguageSeeder;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Services;
use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;

/**
 * @internal
 */
final class PageServicePresetTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = false;
    protected $namespace   = 'App';

    protected function tearDown(): void
    {
        Services::reset();
        parent::tearDown();
    }

    public function testStoreSeedsBlocksFromPreset(): void
    {
        $db = $this->db;
        $db->disableForeignKeyChecks();
        $db->table('cms_block_instance_translations')->truncate();
        $db->table('cms_block_instances')->truncate();
        $db->table('cms_pages')->truncate();
        $db->table('cms_page_translations')->truncate();
        $db->table('cms_content_blocks')->truncate();
        $db->table('cms_languages')->truncate();
        $db->enableForeignKeyChecks();

        (new CmsLanguageSeeder(config('Database'), $db))->run();
        (new CmsBlockTypeSeeder(config('Database'), $db))->run();

        $languageId = (int) ($db->table('cms_languages')->where('code', 'es')->get()->getRowArray()['id'] ?? 0);
        $this->assertGreaterThan(0, $languageId);

        $service = Services::pageService(false);
        $service->store($this->dto([
            'page_type' => 'events',
            'parent_id' => null,
            'published_at' => date('Y-m-d H:i:s'),
            'scheduled_at' => null,
            'translations' => [
                [
                    'language_id' => $languageId,
                    'slug' => 'eventos',
                    'title' => 'Eventos',
                    'excerpt' => 'Descubre la cartelera.',
                    'meta_title' => 'Eventos',
                    'meta_description' => 'Eventos del sitio',
                ],
            ],
        ]));

        $page = $db->table('cms_pages')
            ->where('page_type', 'events')
            ->get()
            ->getRowArray();

        $this->assertNotNull($page);
        $this->assertSame('draft', $page['status']);
        $this->assertSame('0', (string) $page['sort_order']);
        $this->assertSame('1', (string) $page['is_in_sitemap']);
        $this->assertSame('monthly', $page['sitemap_changefreq']);

        $blockRows = $db->table('cms_block_instances')
            ->where('owner_type', 'page')
            ->where('owner_id', (int) $page['id'])
            ->orderBy('sort_order', 'ASC')
            ->get()
            ->getResultArray();

        $this->assertCount(3, $blockRows);

        $keys = array_map(function (array $blockRow) use ($db): string {
            $blockType = $db->table('cms_content_blocks')
                ->where('id', (int) $blockRow['block_id'])
                ->get()
                ->getRowArray();

            return (string) ($blockType['block_key'] ?? '');
        }, $blockRows);

        $this->assertSame(['page_header', 'events_grid', 'image'], $keys);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function dto(array $data): DataTransferObjectInterface
    {
        return new class ($data) implements DataTransferObjectInterface {
            public function __construct(private array $data)
            {
            }

            public function toArray(): array
            {
                return $this->data;
            }
        };
    }
}
