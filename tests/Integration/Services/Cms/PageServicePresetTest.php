<?php

declare(strict_types=1);

namespace Tests\Integration\Services\Cms;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Services;
use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;
use Tests\Support\Fixtures\FixtureValueFactory;

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

    private string $languageCode;

    protected function setUp(): void
    {
        parent::setUp();

        $this->languageCode = (new FixtureValueFactory(self::class))->locale(0);

        if ((int) $this->db->table('cms_languages')->countAllResults() === 0) {
            $this->db->table('cms_languages')->insert([
                'code' => $this->languageCode,
                'name' => 'Fixture Language',
                'native_name' => 'Fixture Language',
                'is_default' => 1,
                'is_active' => 1,
                'sort_order' => 0,
            ]);
        }
    }

    protected function tearDown(): void
    {
        Services::reset();
        parent::tearDown();
    }

    public function testStoreDoesNotAutoSeedBlocksFromService(): void
    {
        $db = $this->db;
        $db->disableForeignKeyChecks();
        $db->query("DELETE FROM `cms_block_instance_translations`");
        $db->query("DELETE FROM `cms_block_instances`");
        $db->query("DELETE FROM `cms_pages`");
        $db->query("DELETE FROM `cms_page_translations`");
        $db->query("DELETE FROM `cms_content_blocks`");
        $db->query("DELETE FROM `cms_languages`");
        $db->enableForeignKeyChecks();

        $db->table('cms_languages')->insert([
            'code' => $this->languageCode,
            'name' => 'Fixture Language',
            'native_name' => 'Fixture Language',
            'is_default' => 1,
            'is_active' => 1,
            'sort_order' => 0,
        ]);
        $languageId = (int) $db->insertID();
        $this->assertGreaterThan(0, $languageId);

        $service = Services::pageService(false);
        $service->store($this->dto([
            'page_type' => 'generic',
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
            ->where('page_type', 'generic')
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
            ->get()
            ->getResultArray();

        $this->assertSame([], $blockRows);
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
