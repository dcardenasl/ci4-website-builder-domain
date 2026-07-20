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
 *   3. Portafolio / Portfolio               → collection index page
 *   4. Bloques / Components                 → components page
 *   5. Multimedia / Media                  → multimedia page
 *   6. Noticias / News                      → noticias collection
 *   7. Contacto / Contact                   → contact page
 *
 * Footer menu (flat):
 *   1. Inicio  2. Quiénes Somos  3. Historia  4. Portafolio  5. Bloques  6. Multimedia  7. Noticias  8. Contacto
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
        $aboutPageId           = $this->pageIdBySlug(['nosotros', 'about']);
        $historyPageId         = $this->pageIdBySlug(['historia', 'history']);
        $portfolioCollectionId = $this->collectionIdByKey('portafolio');
        $portfolioPageId       = $portfolioCollectionId !== null ? $this->pageIdByCollectionId($portfolioCollectionId) : null;
        $componentsPageId      = $this->pageIdBySlug(['bloques', 'components']);
        $mediaPageId           = $this->pageIdBySlug(['multimedia', 'media']);
        $landingPageId         = $this->pageIdBySlug(['landing']);
        $contactPageId         = $this->pageIdByType('contact');
        $legalNoticePageId     = $this->pageIdBySlug(['aviso-legal', 'legal-notice']);
        $privacyPageId         = $this->pageIdBySlug(['politica-privacidad', 'privacy-policy']);
        $cookiesPageId         = $this->pageIdBySlug(['politica-cookies', 'cookie-policy']);
        $dataRightsPageId      = $this->pageIdBySlug(['derechos-datos', 'data-rights']);
        $termsPageId           = $this->pageIdBySlug(['terminos-servicio', 'terms-of-service']);
        $transparencyPageId    = $this->pageIdBySlug(['transparencia', 'transparency']);
        $accessibilityPageId   = $this->pageIdBySlug(['accesibilidad', 'accessibility']);
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
        $mainMenuItemIds = [];

        $mainMenuItemIds[] = $this->upsertMenuItem($mainMenuId, 'page', [
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
        $mainMenuItemIds[] = $nosotrosItemId;

        if ($aboutPageId !== null) {
            $mainMenuItemIds[] = $this->upsertMenuItem($mainMenuId, 'page', [
                'page_id'       => $aboutPageId,
                'entry_id'      => null,
                'collection_id' => null,
                'parent_id'     => $nosotrosItemId,
                'sort_order'    => 1,
            ], ['es' => 'Quiénes Somos', 'en' => 'About Us'], $langIds);
        }

        if ($historyPageId !== null) {
            $mainMenuItemIds[] = $this->upsertMenuItem($mainMenuId, 'page', [
                'page_id'       => $historyPageId,
                'entry_id'      => null,
                'collection_id' => null,
                'parent_id'     => $nosotrosItemId,
                'sort_order'    => 2,
            ], ['es' => 'Historia', 'en' => 'History'], $langIds);
        }

        if ($portfolioCollectionId !== null) {
            $mainMenuItemIds[] = $this->upsertMenuItem($mainMenuId, 'collection_listing', [
                'page_id'       => null,
                'entry_id'      => null,
                'collection_id' => $portfolioCollectionId,
                'parent_id'     => null,
                'sort_order'    => 3,
            ], ['es' => 'Portafolio', 'en' => 'Portfolio'], $langIds);
        }

        if ($mediaPageId !== null) {
            $mainMenuItemIds[] = $this->upsertMenuItem($mainMenuId, 'page', [
                'page_id'       => $mediaPageId,
                'entry_id'      => null,
                'collection_id' => null,
                'parent_id'     => null,
                'sort_order'    => 4,
            ], ['es' => 'Multimedia', 'en' => 'Media'], $langIds);
        }

        // "Ejemplos" dropdown label
        $examplesItemId = $this->upsertMenuItemNoLink($mainMenuId, [
            'parent_id'  => null,
            'sort_order' => 5,
        ], ['es' => 'Ejemplos', 'en' => 'Examples'], $langIds);
        $mainMenuItemIds[] = $examplesItemId;

        if ($landingPageId !== null) {
            $mainMenuItemIds[] = $this->upsertMenuItem($mainMenuId, 'page', [
                'page_id'       => $landingPageId,
                'entry_id'      => null,
                'collection_id' => null,
                'parent_id'     => $examplesItemId,
                'sort_order'    => 1,
            ], ['es' => 'Landing Page', 'en' => 'Landing Page'], $langIds);
        }

        if ($componentsPageId !== null) {
            $mainMenuItemIds[] = $this->upsertMenuItem($mainMenuId, 'page', [
                'page_id'       => $componentsPageId,
                'entry_id'      => null,
                'collection_id' => null,
                'parent_id'     => $examplesItemId,
                'sort_order'    => 2,
            ], ['es' => 'Bloques', 'en' => 'Components'], $langIds);
        }

        $mainMenuItemIds[] = $this->upsertMenuItem($mainMenuId, 'collection_listing', [
            'page_id'       => null,
            'entry_id'      => null,
            'collection_id' => $newsCollectionId,
            'parent_id'     => null,
            'sort_order'    => 6,
        ], ['es' => 'Noticias', 'en' => 'News'], $langIds);

        $mainMenuItemIds[] = $this->upsertMenuItem($mainMenuId, 'page', [
            'page_id'       => $contactPageId,
            'entry_id'      => null,
            'collection_id' => null,
            'parent_id'     => null,
            'sort_order'    => 7,
        ], ['es' => 'Contacto', 'en' => 'Contact'], $langIds);

        $this->pruneMenuItems($mainMenuId, $mainMenuItemIds);

        // ── Footer menu (flat) ─────────────────────────────────────────────────
        $footerMenuId = $this->upsertMenu('footer', 'footer', [
            'es' => 'Pie de página',
            'en' => 'Footer navigation',
        ]);
        $footerMenuItemIds = [];

        $footerMenuItemIds[] = $this->upsertMenuItem($footerMenuId, 'page', [
            'page_id'       => $homePageId,
            'entry_id'      => null,
            'collection_id' => null,
            'parent_id'     => null,
            'sort_order'    => 1,
        ], ['es' => 'Inicio', 'en' => 'Home'], $langIds);

        if ($aboutPageId !== null) {
            $footerMenuItemIds[] = $this->upsertMenuItem($footerMenuId, 'page', [
                'page_id'       => $aboutPageId,
                'entry_id'      => null,
                'collection_id' => null,
                'parent_id'     => null,
                'sort_order'    => 2,
            ], ['es' => 'Quiénes Somos', 'en' => 'About Us'], $langIds);
        }

        if ($historyPageId !== null) {
            $footerMenuItemIds[] = $this->upsertMenuItem($footerMenuId, 'page', [
                'page_id'       => $historyPageId,
                'entry_id'      => null,
                'collection_id' => null,
                'parent_id'     => null,
                'sort_order'    => 3,
            ], ['es' => 'Historia', 'en' => 'History'], $langIds);
        }

        if ($portfolioCollectionId !== null) {
            $footerMenuItemIds[] = $this->upsertMenuItem($footerMenuId, 'collection_listing', [
                'page_id'       => null,
                'entry_id'      => null,
                'collection_id' => $portfolioCollectionId,
                'parent_id'     => null,
                'sort_order'    => 4,
            ], ['es' => 'Portafolio', 'en' => 'Portfolio'], $langIds);
        }

        if ($componentsPageId !== null) {
            $footerMenuItemIds[] = $this->upsertMenuItem($footerMenuId, 'page', [
                'page_id'       => $componentsPageId,
                'entry_id'      => null,
                'collection_id' => null,
                'parent_id'     => null,
                'sort_order'    => 5,
            ], ['es' => 'Bloques', 'en' => 'Components'], $langIds);
        }

        if ($mediaPageId !== null) {
            $footerMenuItemIds[] = $this->upsertMenuItem($footerMenuId, 'page', [
                'page_id'       => $mediaPageId,
                'entry_id'      => null,
                'collection_id' => null,
                'parent_id'     => null,
                'sort_order'    => 6,
            ], ['es' => 'Multimedia', 'en' => 'Media'], $langIds);
        }

        $footerMenuItemIds[] = $this->upsertMenuItem($footerMenuId, 'collection_listing', [
            'page_id'       => null,
            'entry_id'      => null,
            'collection_id' => $newsCollectionId,
            'parent_id'     => null,
            'sort_order'    => 7,
        ], ['es' => 'Noticias', 'en' => 'News'], $langIds);

        if ($landingPageId !== null) {
            $footerMenuItemIds[] = $this->upsertMenuItem($footerMenuId, 'page', [
                'page_id'       => $landingPageId,
                'entry_id'      => null,
                'collection_id' => null,
                'parent_id'     => null,
                'sort_order'    => 8,
            ], ['es' => 'Landing Page', 'en' => 'Landing Page'], $langIds);
        }

        $footerMenuItemIds[] = $this->upsertMenuItem($footerMenuId, 'page', [
            'page_id'       => $contactPageId,
            'entry_id'      => null,
            'collection_id' => null,
            'parent_id'     => null,
            'sort_order'    => 9,
        ], ['es' => 'Contacto', 'en' => 'Contact'], $langIds);

        $this->pruneMenuItems($footerMenuId, $footerMenuItemIds);

        // ── Legal secondary menu (flat) ───────────────────────────────────────
        $legalMenuId = $this->upsertMenu('legal', 'footer_secondary', [
            'es' => 'Enlaces legales',
            'en' => 'Legal links',
        ]);
        $legalMenuItemIds = [];

        if ($legalNoticePageId !== null) {
            $legalMenuItemIds[] = $this->upsertMenuItem($legalMenuId, 'page', [
                'page_id'       => $legalNoticePageId,
                'entry_id'      => null,
                'collection_id' => null,
                'parent_id'     => null,
                'sort_order'    => 1,
            ], ['es' => 'Aviso Legal', 'en' => 'Legal Notice'], $langIds);
        }

        if ($privacyPageId !== null) {
            $legalMenuItemIds[] = $this->upsertMenuItem($legalMenuId, 'page', [
                'page_id'       => $privacyPageId,
                'entry_id'      => null,
                'collection_id' => null,
                'parent_id'     => null,
                'sort_order'    => 2,
            ], ['es' => 'Política de Privacidad', 'en' => 'Privacy Policy'], $langIds);
        }

        if ($cookiesPageId !== null) {
            $legalMenuItemIds[] = $this->upsertMenuItem($legalMenuId, 'page', [
                'page_id'       => $cookiesPageId,
                'entry_id'      => null,
                'collection_id' => null,
                'parent_id'     => null,
                'sort_order'    => 3,
            ], ['es' => 'Política de Cookies', 'en' => 'Cookie Policy'], $langIds);
        }

        if ($dataRightsPageId !== null) {
            $legalMenuItemIds[] = $this->upsertMenuItem($legalMenuId, 'page', [
                'page_id'       => $dataRightsPageId,
                'entry_id'      => null,
                'collection_id' => null,
                'parent_id'     => null,
                'sort_order'    => 4,
            ], ['es' => 'Derechos de Datos', 'en' => 'Data Rights'], $langIds);
        }

        if ($termsPageId !== null) {
            $legalMenuItemIds[] = $this->upsertMenuItem($legalMenuId, 'page', [
                'page_id'       => $termsPageId,
                'entry_id'      => null,
                'collection_id' => null,
                'parent_id'     => null,
                'sort_order'    => 5,
            ], ['es' => 'Términos de Servicio', 'en' => 'Terms of Service'], $langIds);
        }

        if ($transparencyPageId !== null) {
            $legalMenuItemIds[] = $this->upsertMenuItem($legalMenuId, 'page', [
                'page_id'       => $transparencyPageId,
                'entry_id'      => null,
                'collection_id' => null,
                'parent_id'     => null,
                'sort_order'    => 6,
            ], ['es' => 'Transparencia', 'en' => 'Transparency'], $langIds);
        }

        if ($accessibilityPageId !== null) {
            $legalMenuItemIds[] = $this->upsertMenuItem($legalMenuId, 'page', [
                'page_id'       => $accessibilityPageId,
                'entry_id'      => null,
                'collection_id' => null,
                'parent_id'     => null,
                'sort_order'    => 7,
            ], ['es' => 'Accesibilidad', 'en' => 'Accessibility'], $langIds);
        }

        $this->pruneMenuItems($legalMenuId, $legalMenuItemIds);
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

    /**
     * Find page by slug(s). Prioritizes Spanish slug (first in array) over English.
     *
     * @param list<string> $slugs Spanish slug first, then English slug
     */
    private function pageIdBySlug(array $slugs): ?int
    {
        if ($slugs === [] || count($slugs) === 0) {
            return null;
        }

        // Get language IDs for Spanish
        $langIds = $this->langIds(['es', 'en']);
        $spanishLangId = $langIds['es'] ?? null;

        // If we have a Spanish slug, prefer pages with Spanish translations
        if (!empty($slugs[0]) && $spanishLangId !== null) {
            $spanishSlug = $slugs[0];
            $row = $this->db->table('cms_pages')
                ->select('cms_pages.id')
                ->join('cms_page_translations', 'cms_page_translations.page_id = cms_pages.id')
                ->where('cms_pages.deleted_at IS NULL', null, false)
                ->where('cms_page_translations.slug', $spanishSlug)
                ->where('cms_page_translations.language_id', $spanishLangId)
                ->orderBy('cms_pages.id', 'ASC')
                ->get()
                ->getRow();

            if ($row !== null) {
                return (int) $row->id;
            }
        }

        // Fallback: search all provided slugs (including English)
        $row = $this->db->table('cms_pages')
            ->select('cms_pages.id')
            ->join('cms_page_translations', 'cms_page_translations.page_id = cms_pages.id')
            ->where('cms_pages.deleted_at IS NULL', null, false)
            ->whereIn('cms_page_translations.slug', $slugs)
            ->orderBy('cms_pages.id', 'ASC')
            ->get()
            ->getRow();

        return $row !== null ? (int) $row->id : null;
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

    /**
     * Remove stale menu rows that are no longer part of the canonical seed.
     *
     * @param list<int> $keepIds
     */
    private function pruneMenuItems(int $menuId, array $keepIds): void
    {
        $keepIds = array_values(array_unique(array_filter(
            array_map(static fn ($id): int => (int) $id, $keepIds),
            static fn (int $id): bool => $id > 0
        )));

        $builder = $this->db->table('cms_menu_items')->where('menu_id', $menuId);

        if ($keepIds !== []) {
            $builder->whereNotIn('id', $keepIds);
        }

        $builder->delete();
    }
}
