<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use App\Database\Seeds\Concerns\IdempotentSeederSupport;
use CodeIgniter\Database\Seeder;

class SiteLegalMenuSeeder extends Seeder
{
    use IdempotentSeederSupport;

    public function run(): void
    {
        $langIds = $this->langIds(['es', 'en']);
        if (! isset($langIds['es'], $langIds['en'])) {
            echo "SiteLegalMenuSeeder: Spanish and English languages required.\n";
            return;
        }

        // Create legal menu
        $menuId = $this->upsertMenu('legal', 'footer_secondary', [
            'es' => 'Legal',
            'en' => 'Legal',
        ]);

        $legalNoticePageId     = $this->pageIdBySlug(['aviso-legal', 'legal-notice']);
        $privacyPageId         = $this->pageIdBySlug(['politica-privacidad', 'privacy-policy']);
        $cookiesPageId         = $this->pageIdBySlug(['politica-cookies', 'cookie-policy']);
        $dataRightsPageId      = $this->pageIdBySlug(['derechos-datos', 'data-rights']);
        $termsPageId           = $this->pageIdBySlug(['terminos-servicio', 'terms-of-service']);
        $transparencyPageId    = $this->pageIdBySlug(['transparencia', 'transparency']);
        $accessibilityPageId   = $this->pageIdBySlug(['accesibilidad', 'accessibility']);

        $legalMenuItemIds = [];

        if ($legalNoticePageId !== null) {
            $legalMenuItemIds[] = $this->upsertMenuItem($menuId, 'page', [
                'page_id'       => $legalNoticePageId,
                'entry_id'      => null,
                'collection_id' => null,
                'parent_id'     => null,
                'sort_order'    => 1,
            ], ['es' => 'Aviso Legal', 'en' => 'Legal Notice'], $langIds);
        }

        if ($privacyPageId !== null) {
            $legalMenuItemIds[] = $this->upsertMenuItem($menuId, 'page', [
                'page_id'       => $privacyPageId,
                'entry_id'      => null,
                'collection_id' => null,
                'parent_id'     => null,
                'sort_order'    => 2,
            ], ['es' => 'Política de Privacidad', 'en' => 'Privacy Policy'], $langIds);
        }

        if ($cookiesPageId !== null) {
            $legalMenuItemIds[] = $this->upsertMenuItem($menuId, 'page', [
                'page_id'       => $cookiesPageId,
                'entry_id'      => null,
                'collection_id' => null,
                'parent_id'     => null,
                'sort_order'    => 3,
            ], ['es' => 'Política de Cookies', 'en' => 'Cookie Policy'], $langIds);
        }

        if ($dataRightsPageId !== null) {
            $legalMenuItemIds[] = $this->upsertMenuItem($menuId, 'page', [
                'page_id'       => $dataRightsPageId,
                'entry_id'      => null,
                'collection_id' => null,
                'parent_id'     => null,
                'sort_order'    => 4,
            ], ['es' => 'Derechos de Datos', 'en' => 'Data Rights'], $langIds);
        }

        if ($termsPageId !== null) {
            $legalMenuItemIds[] = $this->upsertMenuItem($menuId, 'page', [
                'page_id'       => $termsPageId,
                'entry_id'      => null,
                'collection_id' => null,
                'parent_id'     => null,
                'sort_order'    => 5,
            ], ['es' => 'Términos de Servicio', 'en' => 'Terms of Service'], $langIds);
        }

        if ($transparencyPageId !== null) {
            $legalMenuItemIds[] = $this->upsertMenuItem($menuId, 'page', [
                'page_id'       => $transparencyPageId,
                'entry_id'      => null,
                'collection_id' => null,
                'parent_id'     => null,
                'sort_order'    => 6,
            ], ['es' => 'Transparencia', 'en' => 'Transparency'], $langIds);
        }

        if ($accessibilityPageId !== null) {
            $legalMenuItemIds[] = $this->upsertMenuItem($menuId, 'page', [
                'page_id'       => $accessibilityPageId,
                'entry_id'      => null,
                'collection_id' => null,
                'parent_id'     => null,
                'sort_order'    => 7,
            ], ['es' => 'Accesibilidad', 'en' => 'Accessibility'], $langIds);
        }

        $this->pruneMenuItems($menuId, $legalMenuItemIds);

        echo "✓ Legal menu seeded with " . count($legalMenuItemIds) . " items\n";
    }

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

    private function pageIdBySlug(array $slugs): ?int
    {
        if ($slugs === [] || count($slugs) === 0) {
            return null;
        }

        $langIds = $this->langIds(['es', 'en']);
        $spanishLangId = $langIds['es'] ?? null;

        // Try Spanish slug first
        if (! empty($slugs[0]) && $spanishLangId !== null) {
            $row = $this->db->table('cms_pages')
                ->select('cms_pages.id')
                ->join('cms_page_translations', 'cms_page_translations.page_id = cms_pages.id')
                ->where('cms_pages.deleted_at IS NULL', null, false)
                ->where('cms_page_translations.slug', $slugs[0])
                ->where('cms_page_translations.language_id', $spanishLangId)
                ->orderBy('cms_pages.id', 'ASC')
                ->get()
                ->getRow();

            if ($row !== null) {
                return (int) $row->id;
            }
        }

        // Fallback: any slug
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

    // Helper methods from SiteMenuSeeder
    private function upsertMenu(string $menuKey, string $location, array $titles): ?int
    {
        $now = date('Y-m-d H:i:s');
        $langIds = $this->langIds(['es', 'en']);

        $menuId = $this->upsertRecord('cms_menus', [
            'menu_key' => $menuKey,
        ], [
            'location'  => $location,
            'is_active' => 1,
        ]);

        foreach (['es', 'en'] as $langCode) {
            $this->upsertRecord('cms_menu_translations', [
                'menu_id'     => $menuId,
                'language_id' => $langIds[$langCode],
            ], [
                'name' => $titles[$langCode] ?? $titles['es'],
            ]);
        }

        return $menuId;
    }

    private function upsertMenuItem(int $menuId, string $linkType, array $references, array $labels, array $langIds): ?int
    {
        $payload = [
            'link_type'  => $linkType,
            'is_active'  => 1,
            'css_class'  => null,
            'sort_order' => (int) ($references['sort_order'] ?? 0),
        ];

        $itemId = $this->upsertRecord('cms_menu_items', [
            'menu_id'       => $menuId,
            'parent_id'     => $references['parent_id'] ?? null,
            'link_type'     => $linkType,
            'page_id'       => $references['page_id'] ?? null,
            'entry_id'      => $references['entry_id'] ?? null,
            'collection_id' => $references['collection_id'] ?? null,
            'sort_order'    => (int) ($references['sort_order'] ?? 0),
        ], $payload);

        foreach (['es', 'en'] as $langCode) {
            $this->upsertRecord('cms_menu_item_translations', [
                'menu_item_id' => $itemId,
                'language_id'  => $langIds[$langCode],
            ], [
                'label' => $labels[$langCode] ?? $labels['es'],
            ]);
        }

        return $itemId;
    }

    private function pruneMenuItems(int $menuId, array $keepIds): void
    {
        if (empty($keepIds)) {
            return;
        }

        $this->db->table('cms_menu_items')
            ->where('menu_id', $menuId)
            ->whereNotIn('id', $keepIds)
            ->delete();
    }
}
