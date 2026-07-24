<?php

declare(strict_types=1);

namespace Tests\Integration\Database\Seeds;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class SiteMenuSeederTest extends CIUnitTestCase
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
            'cms_form_field_translations',
            'cms_form_fields',
            'cms_form_translations',
            'cms_forms',
            'cms_menu_item_translations',
            'cms_menu_items',
            'cms_menu_translations',
            'cms_menus',
            'cms_entry_categories',
            'cms_entry_tags',
            'cms_entry_translations',
            'cms_entries',
            'cms_category_translations',
            'cms_categories',
            'cms_tag_translations',
            'cms_tags',
            'cms_page_translations',
            'cms_pages',
            'cms_collection_translations',
            'cms_collections',
            'cms_setting_translations',
            'cms_settings',
            'cms_languages',
        ];

        foreach ($tables as $table) {
            $this->db->query("DELETE FROM `{$table}`");
        }
        $this->db->enableForeignKeyChecks();
    }

    public function testMenuSeederSyncsMainAndFooterWithoutDuplicateTopLevelItems(): void
    {
        $seeder = \Config\Database::seeder();
        $seeder->call(\App\Database\Seeds\SiteBootstrapSeeder::class);

        $mainMenu = $this->db->table('cms_menus')
            ->where('menu_key', 'main')
            ->get()
            ->getRowArray();

        $this->assertNotNull($mainMenu);

        $mainLabels = $this->menuLabels((int) $mainMenu['id'], 'es', null);
        $this->assertSame(
            ['Inicio', 'Nosotros', 'Portafolio', 'Multimedia', 'Ejemplos', 'Noticias', 'Contacto'],
            $mainLabels
        );

        $footerMenu = $this->db->table('cms_menus')
            ->where('menu_key', 'footer')
            ->get()
            ->getRowArray();

        $this->assertNotNull($footerMenu);

        $footerLabels = $this->menuLabels((int) $footerMenu['id'], 'es', null);
        $this->assertSame(
            ['Inicio', 'Quiénes Somos', 'Historia', 'Portafolio', 'Bloques', 'Multimedia', 'Noticias', 'Landing Page', 'Contacto'],
            $footerLabels
        );

        $legalMenu = $this->db->table('cms_menus')
            ->where('menu_key', 'legal')
            ->get()
            ->getRowArray();

        $this->assertNotNull($legalMenu);

        $legalLabels = $this->menuLabels((int) $legalMenu['id'], 'es', null);
        $this->assertSame(
            ['Aviso Legal', 'Política de Privacidad', 'Política de Cookies', 'Derechos de Datos', 'Términos de Servicio', 'Transparencia', 'Accesibilidad'],
            $legalLabels
        );
    }

    /**
     * @return list<string>
     */
    private function menuLabels(int $menuId, string $langCode, ?int $parentId): array
    {
        $builder = $this->db->table('cms_menu_items mi')
            ->select('mt.label')
            ->join('cms_menu_item_translations mt', 'mt.menu_item_id = mi.id')
            ->join('cms_languages l', 'l.id = mt.language_id')
            ->where('mi.menu_id', $menuId)
            ->where('mi.is_active', 1)
            ->where('l.code', $langCode)
            ->orderBy('mi.sort_order', 'ASC')
            ->orderBy('mi.id', 'ASC');

        if ($parentId === null) {
            $builder->where('mi.parent_id IS NULL', null, false);
        } else {
            $builder->where('mi.parent_id', $parentId);
        }

        $rows = $builder->get()->getResultArray();

        return array_map(
            static fn (array $row): string => (string) ($row['label'] ?? ''),
            $rows
        );
    }
}
