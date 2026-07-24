<?php

declare(strict_types=1);

namespace Tests\Integration\Database\Seeds;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class SiteComponentsPageSeederTest extends CIUnitTestCase
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

    public function testComponentsPageSeederCreatesAVisibleContainerHub(): void
    {
        $seeder = \Config\Database::seeder();
        $seeder->call(\App\Database\Seeds\CmsLanguageSeeder::class);
        $seeder->call(\App\Database\Seeds\CmsBlockTypeSeeder::class);
        $seeder->call(\App\Database\Seeds\SiteComponentsPageSeeder::class);

        $page = $this->db->table('cms_pages')
            ->select('cms_pages.*')
            ->join('cms_page_translations', 'cms_page_translations.page_id = cms_pages.id')
            ->where('cms_page_translations.slug', 'bloques')
            ->get()
            ->getRowArray();

        $this->assertNotNull($page);

        $containerBlock = $this->db->table('cms_block_instances')
            ->where('owner_type', 'page')
            ->where('owner_id', (int) $page['id'])
            ->where('parent_instance_id IS NULL', null, false)
            ->where('sort_order', 3)
            ->get()
            ->getRowArray();

        $this->assertNotNull($containerBlock);
        $this->assertSame('container', $this->blockKeyForInstance((int) $containerBlock['block_id']));

        $containerConfig = json_decode((string) ($containerBlock['block_config'] ?? '{}'), true);
        $this->assertIsArray($containerConfig);
        $this->assertStringContainsString('not-prose', (string) ($containerConfig['css_class'] ?? ''));

        $cardsGrid = $this->db->table('cms_block_instances')
            ->where('parent_instance_id', (int) $containerBlock['id'])
            ->where('sort_order', 1)
            ->get()
            ->getRowArray();

        $this->assertNotNull($cardsGrid);
        $this->assertSame('cards_grid', $this->blockKeyForInstance((int) $cardsGrid['block_id']));

        $cards = $this->db->table('cms_block_instances')
            ->where('parent_instance_id', (int) $cardsGrid['id'])
            ->orderBy('sort_order', 'ASC')
            ->get()
            ->getResultArray();

        $this->assertCount(7, $cards);
        $this->assertSame('card_item', $this->blockKeyForInstance((int) $cards[0]['block_id']));

        $esLanguage = $this->db->table('cms_languages')
            ->where('code', 'es')
            ->get()
            ->getRowArray();
        $this->assertNotNull($esLanguage);

        $cardTitles = [];
        foreach ($cards as $card) {
            $translation = $this->db->table('cms_block_instance_translations')
                ->where('instance_id', (int) $card['id'])
                ->where('language_id', (int) $esLanguage['id'])
                ->get()
                ->getRowArray();

            if (! $translation) {
                continue;
            }

            $data = json_decode((string) ($translation['block_data'] ?? '{}'), true);
            if (! is_array($data)) {
                continue;
            }

            $cardTitles[] = (string) ($data['title'] ?? '');
        }

        $this->assertContains('Multimedia', $cardTitles);

        $translation = $this->db->table('cms_block_instance_translations')
            ->where('instance_id', (int) $cards[0]['id'])
            ->get()
            ->getRowArray();

        $this->assertNotNull($translation);
        $data = json_decode((string) ($translation['block_data'] ?? '{}'), true);
        $this->assertIsArray($data);
        $this->assertNotEmpty($data['link_url'] ?? null);

        $expectedLinks = [
            'Inicio' => '/es',
            'Quiénes somos' => '/es/nosotros',
            'Historia' => '/es/historia',
            'Portafolio' => '/es/portafolio',
            'Multimedia' => '/es/multimedia',
            'Noticias' => '/es/noticias',
            'Contacto' => '/es/contacto',
        ];

        foreach ($expectedLinks as $label => $url) {
            $this->assertMenuCardLink($cards, (int) $esLanguage['id'], $label, $url);
        }
    }

    private function blockKeyForInstance(int $blockId): string
    {
        $row = $this->db->table('cms_content_blocks')
            ->where('id', $blockId)
            ->get()
            ->getRowArray();

        return (string) ($row['block_key'] ?? '');
    }

    /**
     * @param array<int, array<string, mixed>> $cards
     */
    private function assertMenuCardLink(array $cards, int $languageId, string $label, string $url): void
    {
        foreach ($cards as $card) {
            $translation = $this->db->table('cms_block_instance_translations')
                ->where('instance_id', (int) $card['id'])
                ->where('language_id', $languageId)
                ->get()
                ->getRowArray();

            if (! $translation) {
                continue;
            }

            $data = json_decode((string) ($translation['block_data'] ?? '{}'), true);
            if (! is_array($data)) {
                continue;
            }

            if ((string) ($data['title'] ?? '') === $label) {
                $this->assertSame($url, (string) ($data['link_url'] ?? ''));

                return;
            }
        }

        $this->fail(sprintf('Menu card "%s" not found.', $label));
    }
}
