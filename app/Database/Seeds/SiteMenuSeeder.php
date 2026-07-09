<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use App\Database\Seeds\Concerns\IdempotentSeederSupport;
use CodeIgniter\Database\Seeder;

/**
 * Seeds the main and footer navigation menus.
 *
 * Main menu (hierarchical):
 *   1. Inicio / Home                        → home page
 *   2. Nosotros / About (no_link dropdown)
 *      2.1 Quiénes Somos / About Us         → about page
 *      2.2 Historia / History               → history page
 *   3. Eventos / Events                     → events page
 *   4. Noticias / News                      → noticias collection
 *   5. Contacto / Contact                   → contact page
 *
 * Footer menu (flat):
 *   1. Inicio  2. Quiénes Somos  3. Historia  4. Eventos  5. Noticias  6. Contacto
 *
 * Idempotent: upserts menus, items, and translations.
 */
class SiteMenuSeeder extends Seeder
{
    use IdempotentSeederSupport;

    public function run(): void
    {
        $langIds = $this->langIds(['es', 'en']);
        if (! isset($langIds['es'], $langIds['en'])) {
            echo "SiteMenuSeeder: missing languages. Seed CmsLanguageSeeder first.\n";
            return;
        }

        $homePageId            = $this->pageIdByType('home');
        $aboutPageId           = $this->pageIdByType('about');
        $historyPageId         = $this->pageIdByType('history');
        $portfolioCollectionId = $this->collectionIdByKey('portafolio');
        $portfolioPageId       = $portfolioCollectionId !== null ? $this->pageIdByCollectionId($portfolioCollectionId) : null;
        $contactPageId         = $this->pageIdByType('contact');
        $newsCollectionId      = $this->collectionIdByKey('noticias');

        if ($homePageId === null || $contactPageId === null || $newsCollectionId === null) {
            echo "SiteMenuSeeder: missing required pages or collection. Seed SitePagesSeeder and NewsCollectionSeeder first.\n";
            return;
        }

        // ── Main menu (with dropdown hierarchy) ───────────────────────────────
        $mainMenuId = $this->upsertMenu('main', 'header', [
            'es' => 'Navegación principal',
            'en' => 'Main navigation',
        ]);

        $this->upsertMenuItem($mainMenuId, 'page', [
            'page_id'       => $homePageId,
            'entry_id'      => null,
            'collection_id' => null,
            'parent_id'     => null,
            'sort_order'    => 1,
        ], ['es' => 'Inicio', 'en' => 'Home'], $langIds);

        // "Nosotros" dropdown label — no URL, no page
        $nosotrosItemId = $this->upsertMenuItemNoLink($mainMenuId, [
            'parent_id'  => null,
            'sort_order' => 2,
        ], ['es' => 'Nosotros', 'en' => 'About'], $langIds);

        if ($aboutPageId !== null) {
            $this->upsertMenuItem($mainMenuId, 'page', [
                'page_id'       => $aboutPageId,
                'entry_id'      => null,
                'collection_id' => null,
                'parent_id'     => $nosotrosItemId,
                'sort_order'    => 1,
            ], ['es' => 'Quiénes Somos', 'en' => 'About Us'], $langIds);
        }

        if ($historyPageId !== null) {
            $this->upsertMenuItem($mainMenuId, 'page', [
                'page_id'       => $historyPageId,
                'entry_id'      => null,
                'collection_id' => null,
                'parent_id'     => $nosotrosItemId,
                'sort_order'    => 2,
            ], ['es' => 'Historia', 'en' => 'History'], $langIds);
        }

        if ($portfolioPageId !== null) {
            $this->upsertMenuItem($mainMenuId, 'page', [
                'page_id'       => $portfolioPageId,
                'entry_id'      => null,
                'collection_id' => null,
                'parent_id'     => null,
                'sort_order'    => 3,
            ], ['es' => 'Portafolio', 'en' => 'Portfolio'], $langIds);
        }

        $this->upsertMenuItem($mainMenuId, 'collection_listing', [
            'page_id'       => null,
            'entry_id'      => null,
            'collection_id' => $newsCollectionId,
            'parent_id'     => null,
            'sort_order'    => 4,
        ], ['es' => 'Noticias', 'en' => 'News'], $langIds);

        $this->upsertMenuItem($mainMenuId, 'page', [
            'page_id'       => $contactPageId,
            'entry_id'      => null,
            'collection_id' => null,
            'parent_id'     => null,
            'sort_order'    => 5,
        ], ['es' => 'Contacto', 'en' => 'Contact'], $langIds);

        // ── Footer menu (flat) ─────────────────────────────────────────────────
        $footerMenuId = $this->upsertMenu('footer', 'footer', [
            'es' => 'Pie de página',
            'en' => 'Footer navigation',
        ]);

        $this->upsertMenuItem($footerMenuId, 'page', [
            'page_id'       => $homePageId,
            'entry_id'      => null,
            'collection_id' => null,
            'parent_id'     => null,
            'sort_order'    => 1,
        ], ['es' => 'Inicio', 'en' => 'Home'], $langIds);

        if ($aboutPageId !== null) {
            $this->upsertMenuItem($footerMenuId, 'page', [
                'page_id'       => $aboutPageId,
                'entry_id'      => null,
                'collection_id' => null,
                'parent_id'     => null,
                'sort_order'    => 2,
            ], ['es' => 'Quiénes Somos', 'en' => 'About Us'], $langIds);
        }

        if ($historyPageId !== null) {
            $this->upsertMenuItem($footerMenuId, 'page', [
                'page_id'       => $historyPageId,
                'entry_id'      => null,
                'collection_id' => null,
                'parent_id'     => null,
                'sort_order'    => 3,
            ], ['es' => 'Historia', 'en' => 'History'], $langIds);
        }

        if ($portfolioPageId !== null) {
            $this->upsertMenuItem($footerMenuId, 'page', [
                'page_id'       => $portfolioPageId,
                'entry_id'      => null,
                'collection_id' => null,
                'parent_id'     => null,
                'sort_order'    => 4,
            ], ['es' => 'Portafolio', 'en' => 'Portfolio'], $langIds);
        }

        $this->upsertMenuItem($footerMenuId, 'collection_listing', [
            'page_id'       => null,
            'entry_id'      => null,
            'collection_id' => $newsCollectionId,
            'parent_id'     => null,
            'sort_order'    => 5,
        ], ['es' => 'Noticias', 'en' => 'News'], $langIds);

        $this->upsertMenuItem($footerMenuId, 'page', [
            'page_id'       => $contactPageId,
            'entry_id'      => null,
            'collection_id' => null,
            'parent_id'     => null,
            'sort_order'    => 6,
        ], ['es' => 'Contacto', 'en' => 'Contact'], $langIds);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /** @param string[] $codes  @return array<string, int> */
    private function langIds(array $codes): array
    {
        $rows = $this->db->table('cms_languages')
            ->whereIn('code', $codes)
            ->get()
            ->getResultArray();

        $map = [];
        foreach ($rows as $row) {
            $map[$row['code']] = (int) $row['id'];
        }

        return $map;
    }

    private function pageIdByType(string $pageType): ?int
    {
        $row = $this->db->table('cms_pages')
            ->where('page_type', $pageType)
            ->where('deleted_at IS NULL', null, false)
            ->get()
            ->getRowArray();

        return $row !== null ? (int) $row['id'] : null;
    }

    private function pageIdByCollectionId(int $collectionId): ?int
    {
        $row = $this->db->table('cms_pages')
            ->where('page_type', 'collection_index')
            ->where('collection_id', $collectionId)
            ->where('deleted_at IS NULL', null, false)
            ->get()
            ->getRowArray();

        return $row !== null ? (int) $row['id'] : null;
    }

    private function collectionIdByKey(string $collectionKey): ?int
    {
        $row = $this->db->table('cms_collections')
            ->where('collection_key', $collectionKey)
            ->get()
            ->getRowArray();

        return $row !== null ? (int) $row['id'] : null;
    }

    /** @param array<string, string> $translations */
    private function upsertMenu(string $menuKey, string $location, array $translations): int
    {
        $menuId = $this->upsertRecord('cms_menus', [
            'menu_key' => $menuKey,
        ], [
            'location'  => $location,
            'is_active' => 1,
        ]);

        if ($menuId === null) {
            throw new \RuntimeException(sprintf('Unable to seed menu "%s".', $menuKey));
        }

        $langIds = $this->langIds(array_keys($translations));
        foreach ($translations as $langCode => $name) {
            $langId = $langIds[$langCode] ?? null;
            if ($langId === null) {
                continue;
            }
            $this->upsertMenuTranslation($menuId, $langId, $name);
        }

        return $menuId;
    }

    private function upsertMenuTranslation(int $menuId, int $languageId, string $name): void
    {
        $this->upsertRecord('cms_menu_translations', [
            'menu_id'     => $menuId,
            'language_id' => $languageId,
        ], [
            'name' => $name,
        ]);
    }

    /**
     * Upsert a regular menu item (page, entry, collection_listing, custom_url).
     * Keyed by: menu_id + link_type + page_id/entry_id/collection_id + parent_id + sort_order.
     *
     * @param array<string, int|null> $references  Must include page_id, entry_id, collection_id, parent_id, sort_order
     * @param array<string, string>   $translations
     * @param array<string, int>      $langIds
     */
    private function upsertMenuItem(int $menuId, string $linkType, array $references, array $translations, array $langIds): int
    {
        $menuItemId = $this->upsertMenuItemRecord($menuId, $linkType, $references);

        if ($menuItemId === null) {
            throw new \RuntimeException(sprintf(
                'Unable to seed menu item "%s" for menu %d.',
                $linkType,
                $menuId
            ));
        }

        foreach ($translations as $langCode => $label) {
            $langId = $langIds[$langCode] ?? null;
            if ($langId === null) {
                continue;
            }
            $this->upsertMenuItemTranslation($menuItemId, $langId, $label);
        }

        return $menuItemId;
    }

    /**
     * @param array<string, int|null> $references
     */
    private function upsertMenuItemRecord(int $menuId, string $linkType, array $references): ?int
    {
        $payload = [
            'menu_id'       => $menuId,
            'parent_id'     => $references['parent_id'] ?? null,
            'link_type'     => $linkType,
            'page_id'       => $references['page_id'] ?? null,
            'entry_id'      => $references['entry_id'] ?? null,
            'collection_id' => $references['collection_id'] ?? null,
            'link_target'   => '_self',
            'icon'          => null,
            'css_class'     => null,
            'sort_order'    => (int) ($references['sort_order'] ?? 0),
            'is_active'     => 1,
        ];

        return $this->upsertRecord('cms_menu_items', [
            'menu_id'       => $menuId,
            'parent_id'     => $references['parent_id'] ?? null,
            'link_type'     => $linkType,
            'page_id'       => $references['page_id'] ?? null,
            'entry_id'      => $references['entry_id'] ?? null,
            'collection_id' => $references['collection_id'] ?? null,
            'sort_order'    => (int) ($references['sort_order'] ?? 0),
        ], $payload);
    }

    /**
     * Upsert a no_link menu item (dropdown label with no URL).
     * Keyed by: menu_id + link_type='no_link' + parent_id + sort_order.
     *
     * @param array<string, int|null> $references  Must include parent_id, sort_order
     * @param array<string, string>   $translations
     * @param array<string, int>      $langIds
     */
    private function upsertMenuItemNoLink(int $menuId, array $references, array $translations, array $langIds): int
    {
        $menuItemId = $this->upsertMenuItemRecord($menuId, 'no_link', $references);

        if ($menuItemId === null) {
            throw new \RuntimeException(sprintf('Unable to seed no-link menu item for menu %d.', $menuId));
        }

        foreach ($translations as $langCode => $label) {
            $langId = $langIds[$langCode] ?? null;
            if ($langId === null) {
                continue;
            }
            $this->upsertMenuItemTranslation($menuItemId, $langId, $label);
        }

        return $menuItemId;
    }

    private function upsertMenuItemTranslation(int $menuItemId, int $languageId, string $label): void
    {
        $this->upsertRecord('cms_menu_item_translations', [
            'menu_item_id' => $menuItemId,
            'language_id'  => $languageId,
        ], [
            'label'      => $label,
            'custom_url' => null,
        ]);
    }
}
