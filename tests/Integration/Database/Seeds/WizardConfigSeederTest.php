<?php

declare(strict_types=1);

namespace Tests\Integration\Database\Seeds;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class WizardConfigSeederTest extends CIUnitTestCase
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
        foreach (['cms_collection_translations', 'cms_collections', 'cms_languages'] as $table) {
            $this->db->query("DELETE FROM `{$table}`");
        }
        $this->db->enableForeignKeyChecks();
    }

    /**
     * Simulates the exact 2026-07-22 incident: a collection whose persisted
     * block_template still has the stale landing-page blocks (e.g. from an
     * old NewsCollectionSeeder run, or a hand-edited row). WizardConfigSeeder
     * must be able to repair it back to the canonical 2-block preset without
     * requiring the collection to be dropped and reseeded from scratch.
     */
    public function testRepairsStaleBlockTemplateForNewsAndPortfolio(): void
    {
        $staleBlocks = json_encode([
            'version' => '1.0',
            'blocks' => [
                ['block_key' => 'rich_text', 'label' => 'Titular', 'required' => true, 'locked' => true, 'sort_order' => 1],
                ['block_key' => 'image', 'label' => 'Imagen', 'required' => false, 'locked' => false, 'sort_order' => 2],
                ['block_key' => 'page_header', 'label' => 'Encabezado', 'required' => false, 'locked' => false, 'sort_order' => 3],
                ['block_key' => 'hero_banner', 'label' => 'Hero', 'required' => false, 'locked' => false, 'sort_order' => 4],
                ['block_key' => 'cta', 'label' => 'CTA', 'required' => false, 'locked' => false, 'sort_order' => 5],
                ['block_key' => 'alert', 'label' => 'Alerta', 'required' => false, 'locked' => false, 'sort_order' => 6],
            ],
        ]);

        $this->db->table('cms_collections')->insert([
            'collection_key'  => 'noticias',
            'collection_type' => 'news',
            'is_active'       => 1,
            'block_template'  => $staleBlocks,
            'wizard_config'   => json_encode(['type' => 'news', 'steps' => []]),
        ]);
        $newsId = (int) $this->db->insertID();

        $this->db->table('cms_collections')->insert([
            'collection_key'  => 'portafolio',
            'collection_type' => 'portfolio',
            'is_active'       => 1,
            'block_template'  => $staleBlocks,
            'wizard_config'   => json_encode(['type' => 'portfolio', 'steps' => []]),
        ]);
        $portfolioId = (int) $this->db->insertID();

        $seeder = \Config\Database::seeder();
        $seeder->call(\App\Database\Seeds\WizardConfigSeeder::class);

        $news = $this->db->table('cms_collections')->where('id', $newsId)->get()->getRowArray();
        $portfolio = $this->db->table('cms_collections')->where('id', $portfolioId)->get()->getRowArray();

        $this->assertSame(
            ['rich_text', 'image'],
            array_column(json_decode((string) $news['block_template'], true)['blocks'], 'block_key')
        );
        $this->assertSame(
            ['image', 'rich_text'],
            array_column(json_decode((string) $portfolio['block_template'], true)['blocks'], 'block_key')
        );
    }
}
