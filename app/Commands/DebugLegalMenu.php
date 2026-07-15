<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;

class DebugLegalMenu extends BaseCommand
{
    protected $group = 'Debug';
    protected $name = 'debug:legal-menu';
    protected $description = 'Debug legal menu';

    public function run(array $params = []): void
    {
        $db = \Config\Database::connect();

        echo "=== LEGAL MENU ITEMS ===\n";
        $result = $db->table('cms_menu_items')
            ->join('cms_menus', 'cms_menus.id = cms_menu_items.menu_id')
            ->where('cms_menus.menu_key', 'legal')
            ->select('cms_menu_items.id, cms_menu_items.page_id, cms_menu_items.link_type, cms_menu_items.sort_order')
            ->orderBy('cms_menu_items.sort_order')
            ->get();
        $items = $result ? $result->getResultArray() : [];

        echo "Found " . count($items) . " items\n";
        foreach ($items as $item) {
            echo "  Item " . $item['id'] . ": page_id=" . ($item['page_id'] ?? 'null') . ", link_type=" . $item['link_type'] . "\n";
        }

        echo "\n=== LEGAL PAGES ===\n";
        $result = $db->table('cms_pages')
            ->join('cms_page_translations', 'cms_page_translations.page_id = cms_pages.id')
            ->whereIn('cms_page_translations.slug', ['aviso-legal', 'politica-privacidad', 'politica-cookies', 'terminos-servicio', 'legal-notice', 'privacy-policy', 'cookie-policy', 'terms-of-service'])
            ->select('cms_pages.id, cms_pages.page_type, cms_page_translations.slug')
            ->orderBy('cms_pages.id')
            ->get();
        $pages = $result ? $result->getResultArray() : [];

        foreach ($pages as $page) {
            echo "Page " . $page['id'] . ": " . $page['slug'] . " (type: " . $page['page_type'] . ")\n";
        }

        echo "\n=== LEGAL MENU ITEM TRANSLATIONS ===\n";
        $result = $db->table('cms_menu_item_translations')
            ->join('cms_languages', 'cms_languages.id = cms_menu_item_translations.language_id')
            ->whereIn('cms_menu_item_translations.menu_item_id', [59, 60, 61, 62])
            ->select('cms_menu_item_translations.menu_item_id, cms_languages.code, cms_menu_item_translations.label')
            ->get();
        $translations = $result ? $result->getResultArray() : [];

        foreach ($translations as $trans) {
            echo "Item " . $trans['menu_item_id'] . " ({$trans['code']}): " . $trans['label'] . "\n";
        }
    }
}
