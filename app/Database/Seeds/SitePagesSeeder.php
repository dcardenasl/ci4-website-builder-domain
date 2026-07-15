<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use App\Database\Seeds\Concerns\IdempotentSeederSupport;
use CodeIgniter\Database\Seeder;

class SitePagesSeeder extends Seeder
{
    use IdempotentSeederSupport;

    public function run(): void
    {
        $langIds = $this->langIds(['es', 'en']);
        if (! isset($langIds['es'], $langIds['en'])) {
            echo "SitePagesSeeder: missing languages. Seed CmsLanguageSeeder first.\n";
            return;
        }

        $now = date('Y-m-d H:i:s');

        $homeId = $this->upsertPage('home', [
            'page_type'          => 'home',
            'status'             => 'published',
            'published_at'       => $now,
            'scheduled_at'       => null,
            'sort_order'         => 10,
            'sitemap_priority'   => '0.8',
            'sitemap_changefreq' => 'weekly',
            'is_in_sitemap'      => 1,
        ]);

        $this->upsertPageTranslation($homeId, $langIds['es'], [
            'slug'             => 'home',
            'title'            => 'Inicio',
            'excerpt'          => 'Página principal del sitio.',
            'meta_title'       => 'Inicio | Mi Sitio',
            'meta_description' => 'Bienvenido a Mi Sitio.',
            'canonical_url'    => null,
            'robots'           => 'index, follow',
            'schema_data'      => null,
        ]);
        $this->upsertPageTranslation($homeId, $langIds['en'], [
            'slug'             => 'home',
            'title'            => 'Home',
            'excerpt'          => 'The main landing page.',
            'meta_title'       => 'Home | My Site',
            'meta_description' => 'Welcome to My Site.',
            'canonical_url'    => null,
            'robots'           => 'index, follow',
            'schema_data'      => null,
        ]);

        $contactId = $this->upsertPage('contact', [
            'page_type'          => 'contact',
            'status'             => 'published',
            'published_at'       => $now,
            'scheduled_at'       => null,
            'sort_order'         => 20,
            'sitemap_priority'   => '0.6',
            'sitemap_changefreq' => 'monthly',
            'is_in_sitemap'      => 1,
        ]);

        $this->upsertPageTranslation($contactId, $langIds['es'], [
            'slug'             => 'contacto',
            'title'            => 'Contacto',
            'excerpt'          => 'Formulario y datos de contacto.',
            'meta_title'       => 'Contacto | Mi Sitio',
            'meta_description' => 'Ponte en contacto con nosotros.',
            'canonical_url'    => null,
            'robots'           => 'index, follow',
            'schema_data'      => null,
        ]);
        $this->upsertPageTranslation($contactId, $langIds['en'], [
            'slug'             => 'contact',
            'title'            => 'Contact',
            'excerpt'          => 'Contact form and details.',
            'meta_title'       => 'Contact | My Site',
            'meta_description' => 'Get in touch with us.',
            'canonical_url'    => null,
            'robots'           => 'index, follow',
            'schema_data'      => null,
        ]);

        // ── 404 Page ────────────────────────────────────────────────────────
        $notFoundId = $this->upsertPage('404', [
            'page_type'          => '404',
            'status'             => 'published',
            'published_at'       => $now,
            'scheduled_at'       => null,
            'sort_order'         => 900,
            'sitemap_priority'   => '0.1',
            'sitemap_changefreq' => 'never',
            'is_in_sitemap'      => 0,
        ]);

        $this->upsertPageTranslation($notFoundId, $langIds['es'], [
            'slug'             => '404',
            'title'            => 'Página no encontrada',
            'excerpt'          => 'La página que buscas no existe o ha sido movida.',
            'meta_title'       => 'Página no encontrada | Mi Sitio',
            'meta_description' => 'La página solicitada no está disponible.',
            'canonical_url'    => null,
            'robots'           => 'noindex, nofollow',
            'schema_data'      => null,
        ]);
        $this->upsertPageTranslation($notFoundId, $langIds['en'], [
            'slug'             => '404',
            'title'            => 'Page Not Found',
            'excerpt'          => 'The page you are looking for does not exist or has been moved.',
            'meta_title'       => 'Page Not Found | My Site',
            'meta_description' => 'The requested page is not available.',
            'canonical_url'    => null,
            'robots'           => 'noindex, nofollow',
            'schema_data'      => null,
        ]);

        // ── 500 Page ────────────────────────────────────────────────────────
        $internalErrorId = $this->upsertPage('500', [
            'page_type'          => '500',
            'status'             => 'published',
            'published_at'       => $now,
            'scheduled_at'       => null,
            'sort_order'         => 910,
            'sitemap_priority'   => '0.1',
            'sitemap_changefreq' => 'never',
            'is_in_sitemap'      => 0,
        ]);

        $this->upsertPageTranslation($internalErrorId, $langIds['es'], [
            'slug'             => '500',
            'title'            => 'Error interno del servidor',
            'excerpt'          => 'Ha ocurrido un error inesperado. Estamos trabajando para solucionarlo.',
            'meta_title'       => 'Error interno | Mi Sitio',
            'meta_description' => 'Error interno en el servidor.',
            'canonical_url'    => null,
            'robots'           => 'noindex, nofollow',
            'schema_data'      => null,
        ]);
        $this->upsertPageTranslation($internalErrorId, $langIds['en'], [
            'slug'             => '500',
            'title'            => 'Internal Server Error',
            'excerpt'          => 'An unexpected error has occurred. We are working to fix it.',
            'meta_title'       => 'Internal Server Error | My Site',
            'meta_description' => 'Internal server error occurred.',
            'canonical_url'    => null,
            'robots'           => 'noindex, nofollow',
            'schema_data'      => null,
        ]);

        // ── Maintenance Page ────────────────────────────────────────────────
        $maintenanceId = $this->upsertPage('maintenance', [
            'page_type'          => 'maintenance',
            'status'             => 'published',
            'published_at'       => $now,
            'scheduled_at'       => null,
            'sort_order'         => 920,
            'sitemap_priority'   => '0.1',
            'sitemap_changefreq' => 'never',
            'is_in_sitemap'      => 0,
        ]);

        $this->upsertPageTranslation($maintenanceId, $langIds['es'], [
            'slug'             => 'mantenimiento',
            'title'            => 'Sitio en mantenimiento',
            'excerpt'          => 'Estamos realizando tareas de mantenimiento programado. Volveremos pronto.',
            'meta_title'       => 'Mantenimiento | Mi Sitio',
            'meta_description' => 'El sitio web se encuentra en mantenimiento temporal.',
            'canonical_url'    => null,
            'robots'           => 'noindex, nofollow',
            'schema_data'      => null,
        ]);
        $this->upsertPageTranslation($maintenanceId, $langIds['en'], [
            'slug'             => 'maintenance',
            'title'            => 'Site Under Maintenance',
            'excerpt'          => 'We are currently undergoing scheduled maintenance. We will be back shortly.',
            'meta_title'       => 'Maintenance | My Site',
            'meta_description' => 'The website is temporarily offline for maintenance.',
            'canonical_url'    => null,
            'robots'           => 'noindex, nofollow',
            'schema_data'      => null,
        ]);
    }

    /**
     * @param array<int, string> $codes
     * @return array<string, int>
     */
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

    /**
     * @param array<string, mixed> $pageData
     */
    private function upsertPage(string $pageType, array $pageData): int
    {
        $pageId = $this->upsertRecord('cms_pages', [
            'page_type'  => $pageType,
            'deleted_at' => null,
        ], array_merge($pageData, [
            'page_type'  => $pageType,
            'deleted_at' => null,
        ]));

        if ($pageId === null) {
            throw new \RuntimeException(sprintf('SitePagesSeeder: unable to seed page "%s".', $pageType));
        }

        return $pageId;
    }

    /**
     * @param array<string, mixed> $translationData
     */
    private function upsertPageTranslation(int $pageId, int $languageId, array $translationData): void
    {
        $slug = (string) ($translationData['slug'] ?? '');
        if ($slug !== '') {
            $slugConflict = $this->db->table('cms_page_translations')
                ->where('language_id', $languageId)
                ->where('slug', $slug)
                ->get()
                ->getRowArray();
            if ($slugConflict !== null && (int) $slugConflict['page_id'] !== $pageId) {
                $this->db->table('cms_page_translations')
                    ->where('id', (int) $slugConflict['id'])
                    ->update(array_merge($translationData, [
                        'page_id'     => $pageId,
                        'language_id' => $languageId,
                        'updated_at'   => date('Y-m-d H:i:s'),
                    ]));

                return;
            }
        }

        $this->upsertRecord('cms_page_translations', [
            'page_id'     => $pageId,
            'language_id' => $languageId,
        ], $translationData);
    }
}
