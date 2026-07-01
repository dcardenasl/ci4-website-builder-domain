<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Creates the Events / Eventos page and seeds the following blocks:
 *   page_header, events_grid, image.
 *
 * Idempotent: upserts the page, its translations, block instances,
 * and block translations.
 */
class SiteEventsPageSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CmsLanguageSeeder::class);
        $this->call(CmsBlockTypeSeeder::class);

        $langIds = $this->langIds(['es', 'en']);
        if (! isset($langIds['es'], $langIds['en'])) {
            echo "SiteEventsPageSeeder: missing languages. Seed CmsLanguageSeeder first.\n";
            return;
        }

        $eventsPageId = $this->upsertPage();
        $this->upsertPageTranslation($eventsPageId, $langIds['es'], [
            'slug'             => 'eventos',
            'title'            => 'Eventos',
            'excerpt'          => 'Descubre nuestra cartelera de eventos y actividades.',
            'meta_title'       => 'Eventos | Mi Sitio',
            'meta_description' => 'Descubre nuestra cartelera de eventos y actividades próximas.',
            'canonical_url'    => null,
            'robots'           => 'index, follow',
            'schema_data'      => null,
        ]);
        $this->upsertPageTranslation($eventsPageId, $langIds['en'], [
            'slug'             => 'events',
            'title'            => 'Events',
            'excerpt'          => 'Discover our events calendar and upcoming activities.',
            'meta_title'       => 'Events | My Site',
            'meta_description' => 'Discover our events calendar and upcoming activities.',
            'canonical_url'    => null,
            'robots'           => 'index, follow',
            'schema_data'      => null,
        ]);

        $blockIds = $this->blockIds(['page_header', 'events_grid', 'image']);

        // ── 1. page_header ────────────────────────────────────────────────────
        $this->upsertBlockWithTranslations(
            $eventsPageId,
            'page',
            $blockIds,
            'page_header',
            1,
            ['bg_color' => 'bg-gray-100', 'css_class' => ''],
            [
                'es' => [
                    'heading'          => 'Eventos',
                    'subheading'       => 'Descubre nuestra cartelera de eventos.',
                    'breadcrumb_label' => 'Inicio',
                    'breadcrumb_url'   => '/',
                ],
                'en' => [
                    'heading'          => 'Events',
                    'subheading'       => 'Discover our events calendar.',
                    'breadcrumb_label' => 'Home',
                    'breadcrumb_url'   => '/',
                ],
            ],
            $langIds
        );

        // ── 2. events_grid ────────────────────────────────────────────────────
        $this->upsertBlockWithTranslations(
            $eventsPageId,
            'page',
            $blockIds,
            'events_grid',
            2,
            ['collection_key' => 'cartelera', 'items_limit' => 6, 'css_class' => ''],
            [
                'es' => [
                    'section_title'    => 'Próximos Eventos',
                    'section_subtitle' => 'Explora nuestra programación y reserva tu lugar.',
                    'view_all_label'   => 'Ver toda la cartelera',
                    'view_all_url'     => '/eventos',
                    'empty_message'    => 'No hay eventos programados por el momento.',
                ],
                'en' => [
                    'section_title'    => 'Upcoming Events',
                    'section_subtitle' => 'Explore our schedule and reserve your spot.',
                    'view_all_label'   => 'View full calendar',
                    'view_all_url'     => '/events',
                    'empty_message'    => 'No events are currently scheduled.',
                ],
            ],
            $langIds
        );

        // ── 3. image (standalone, banner-style) ───────────────────────────────
        $this->upsertBlockWithTranslations(
            $eventsPageId,
            'page',
            $blockIds,
            'image',
            3,
            ['aspect_ratio' => '16/9', 'css_class' => ''],
            [
                'es' => [
                    'alt'     => 'Imagen de la sección de eventos',
                    'caption' => 'Nuestros eventos reúnen a personas apasionadas.',
                ],
                'en' => [
                    'alt'     => 'Events section image',
                    'caption' => 'Our events bring together passionate people.',
                ],
            ],
            $langIds
        );
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function upsertPage(): int
    {
        $existing = $this->db->table('cms_pages')
            ->where('page_type', 'events')
            ->where('deleted_at IS NULL', null, false)
            ->get()
            ->getRowArray();

        $payload = [
            'page_type'          => 'events',
            'status'             => 'published',
            'published_at'       => date('Y-m-d H:i:s'),
            'scheduled_at'       => null,
            'sort_order'         => 40,
            'sitemap_priority'   => '0.8',
            'sitemap_changefreq' => 'weekly',
            'is_in_sitemap'      => 1,
        ];

        if ($existing === null) {
            $this->db->table('cms_pages')->insert($payload);
            return (int) $this->db->insertID();
        }

        $this->db->table('cms_pages')->where('id', (int) $existing['id'])->update($payload);
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
        if ($slug !== '' && $existing === null) {
            $conflict = $this->db->table('cms_page_translations')
                ->where('language_id', $languageId)
                ->where('slug', $slug)
                ->get()
                ->getRowArray();
            if ($conflict !== null && (int) $conflict['page_id'] !== $pageId) {
                return;
            }
        }

        $payload = array_merge(['page_id' => $pageId, 'language_id' => $languageId], $translationData, ['updated_at' => date('Y-m-d H:i:s')]);

        if ($existing === null) {
            $this->db->table('cms_page_translations')->insert(array_merge($payload, ['created_at' => date('Y-m-d H:i:s')]));
            return;
        }

        unset($payload['page_id'], $payload['language_id'], $payload['created_at']);
        $this->db->table('cms_page_translations')->where('id', (int) $existing['id'])->update($payload);
    }

    /**
     * @param array<string, int>                  $blockIds
     * @param array<string, array<string, mixed>> $translations
     * @param array<string, int>                  $langIds
     * @param array<string, mixed>                $config
     */
    private function upsertBlockWithTranslations(
        int    $pageId,
        string $ownerType,
        array  $blockIds,
        string $blockKey,
        int    $sortOrder,
        array  $config,
        array  $translations,
        array  $langIds
    ): int {
        $blockId = $blockIds[$blockKey] ?? null;
        if ($blockId === null) {
            echo "SiteEventsPageSeeder: block type '{$blockKey}' not found — skipped.\n";
            return 0;
        }

        $existing = $this->db->table('cms_block_instances')
            ->where('block_id', $blockId)
            ->where('owner_type', $ownerType)
            ->where('owner_id', $pageId)
            ->where('sort_order', $sortOrder)
            ->where('parent_instance_id IS NULL', null, false)
            ->get()
            ->getRowArray();

        $payload = [
            'block_id'           => $blockId,
            'owner_type'         => $ownerType,
            'owner_id'           => $pageId,
            'parent_instance_id' => null,
            'sort_order'         => $sortOrder,
            'column_index'       => null,
            'is_active'          => 1,
            'block_config'       => json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];

        if ($existing === null) {
            $this->db->table('cms_block_instances')->insert(array_merge($payload, [
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]));
            $instanceId = (int) $this->db->insertID();
        } else {
            $instanceId = (int) $existing['id'];
            $this->db->table('cms_block_instances')
                ->where('id', $instanceId)
                ->update(array_merge($payload, ['updated_at' => date('Y-m-d H:i:s')]));
        }

        foreach ($translations as $langCode => $data) {
            $langId = $langIds[$langCode] ?? null;
            if ($langId === null || ! is_array($data) || $data === []) {
                continue;
            }
            $this->upsertTranslation($instanceId, $langId, $data);
        }

        return $instanceId;
    }

    /**
     * @param string[] $keys
     * @return array<string, int>
     */
    private function blockIds(array $keys): array
    {
        $rows = $this->db->table('cms_content_blocks')
            ->whereIn('block_key', $keys)
            ->get()
            ->getResultArray();

        $map = [];
        foreach ($rows as $row) {
            $map[$row['block_key']] = (int) $row['id'];
        }

        return $map;
    }

    /**
     * @param string[] $codes
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
     * @param array<string, mixed> $blockData
     */
    private function upsertTranslation(int $instanceId, int $languageId, array $blockData): void
    {
        $existing = $this->db->table('cms_block_instance_translations')
            ->where('instance_id', $instanceId)
            ->where('language_id', $languageId)
            ->get()
            ->getRowArray();

        $payload = [
            'instance_id'  => $instanceId,
            'language_id'  => $languageId,
            'block_data'   => json_encode($blockData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'is_published' => 1,
        ];

        if ($existing === null) {
            $this->db->table('cms_block_instance_translations')->insert(array_merge($payload, [
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]));
            return;
        }

        $this->db->table('cms_block_instance_translations')
            ->where('id', (int) $existing['id'])
            ->update(array_merge($payload, ['updated_at' => date('Y-m-d H:i:s')]));
    }
}
