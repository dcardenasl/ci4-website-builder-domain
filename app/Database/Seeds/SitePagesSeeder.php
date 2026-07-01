<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class SitePagesSeeder extends Seeder
{
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
        $existing = $this->db->table('cms_pages')
            ->where('page_type', $pageType)
            ->where('deleted_at IS NULL', null, false)
            ->get()
            ->getRowArray();

        $payload = array_merge($pageData, [
            'page_type' => $pageType,
        ]);

        if ($existing === null) {
            $this->db->table('cms_pages')->insert($payload);
            return (int) $this->db->insertID();
        }

        $this->db->table('cms_pages')
            ->where('id', (int) $existing['id'])
            ->update($pageData);

        return (int) $existing['id'];
    }

    /**
     * @param array<string, mixed> $translationData
     */
    private function upsertPageTranslation(int $pageId, int $languageId, array $translationData): void
    {
        $existing = $this->db->table('cms_page_translations')
            ->where('page_id', $pageId)
            ->where('language_id', $languageId)
            ->get()
            ->getRowArray();

        $slug = (string) ($translationData['slug'] ?? '');
        $slugConflict = null;
        if ($slug !== '') {
            $slugConflict = $this->db->table('cms_page_translations')
                ->where('language_id', $languageId)
                ->where('slug', $slug)
                ->get()
                ->getRowArray();
        }

        $payload = array_merge([
            'page_id'     => $pageId,
            'language_id' => $languageId,
        ], $translationData, [
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        if ($existing === null) {
            if ($slugConflict !== null && (int) $slugConflict['page_id'] !== $pageId) {
                $this->db->table('cms_page_translations')
                    ->where('id', (int) $slugConflict['id'])
                    ->update($payload + [
                        'page_id' => $pageId,
                    ]);

                return;
            }

            $payload['created_at'] = date('Y-m-d H:i:s');
            $this->db->table('cms_page_translations')->insert($payload);
            return;
        }

        unset($payload['page_id'], $payload['language_id'], $payload['created_at']);
        $this->db->table('cms_page_translations')
            ->where('id', (int) $existing['id'])
            ->update($payload);
    }
}
