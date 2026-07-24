<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use App\Database\Seeds\Concerns\IdempotentSeederSupport;
use CodeIgniter\Database\Seeder;

/**
 * Creates the News / Noticias collection index page and seeds blocks:
 *   page_header, rich_text, collection_listing.
 *
 * Idempotent: upserts page, translations, block instances, and block translations.
 */
class SiteNewsPageSeeder extends Seeder
{
    use IdempotentSeederSupport;

    public function run(): void
    {
        $langIds = $this->langIds(['es', 'en']);
        if (! isset($langIds['es'], $langIds['en'])) {
            echo "SiteNewsPageSeeder: missing languages. Seed CmsLanguageSeeder first.\n";
            return;
        }

        $collectionId = $this->collectionIdByKey('noticias');
        if ($collectionId === null) {
            echo "SiteNewsPageSeeder: noticias collection not found. Seed NewsCollectionSeeder first.\n";
            return;
        }

        $newsPageId = $this->upsertPage($collectionId);
        $this->upsertPageTranslation($newsPageId, $langIds['es'], [
            'slug'             => 'noticias',
            'title'            => 'Noticias',
            'excerpt'          => 'Mantente actualizado con las últimas novedades.',
            'meta_title'       => 'Noticias | Mi Sitio',
            'meta_description' => 'Mantente actualizado con las últimas novedades y artículos.',
            'canonical_url'    => null,
            'robots'           => 'index, follow',
            'schema_data'      => null,
        ]);
        $this->upsertPageTranslation($newsPageId, $langIds['en'], [
            'slug'             => 'news',
            'title'            => 'News',
            'excerpt'          => 'Stay updated with the latest news and updates.',
            'meta_title'       => 'News | My Site',
            'meta_description' => 'Stay updated with our latest news and articles.',
            'canonical_url'    => null,
            'robots'           => 'index, follow',
            'schema_data'      => null,
        ]);

        $blockIds = $this->blockIds(['page_header', 'collection_listing', 'rich_text']);
        $keptInstanceIds = [];

        // ── 1. page_header ────────────────────────────────────────────────────
        $this->trackInstanceId($keptInstanceIds, $this->upsertBlockWithTranslations(
            $newsPageId,
            'page',
            $blockIds,
            'page_header',
            1,
            ['bg_color' => 'bg-blue-50', 'css_class' => ''],
            [
                'es' => [
                    'heading'          => 'Noticias',
                    'subheading'       => 'Mantente al día con nuestras publicaciones.',
                    'breadcrumb_label' => 'Inicio',
                    'breadcrumb_url'   => '/',
                ],
                'en' => [
                    'heading'          => 'News',
                    'subheading'       => 'Stay up to date with our publications.',
                    'breadcrumb_label' => 'Home',
                    'breadcrumb_url'   => '/',
                ],
            ],
            $langIds
        ));

        // ── 2. rich_text intro ─────────────────────────────────────────────────
        $this->trackInstanceId($keptInstanceIds, $this->upsertBlockWithTranslations(
            $newsPageId,
            'page',
            $blockIds,
            'rich_text',
            2,
            [],
            [
                'es' => [
                    'content' => '<p>Explora nuestras últimas publicaciones, artículos y noticias sobre tendencias de la industria, actualizaciones de proyectos y más.</p>',
                ],
                'en' => [
                    'content' => '<p>Explore our latest publications, articles, and news about industry trends, project updates, and more.</p>',
                ],
            ],
            $langIds
        ));

        // ── 3. collection_listing (noticias) ───────────────────────────────────
        $this->trackInstanceId($keptInstanceIds, $this->upsertBlockWithTranslations(
            $newsPageId,
            'page',
            $blockIds,
            'collection_listing',
            3,
            [
                'collection_id'    => $collectionId,
                'per_page'         => 12,
                'order_by'         => 'published_at',
                'order_direction'  => 'desc',
                'layout_variant'   => 'news',
                'show_search'      => 1,
                'show_categories'  => 1,
                'show_tags'        => 1,
                'css_class'        => '',
            ],
            [
                'es' => [
                    'intro_title'   => 'Listado completo',
                    'intro_text'    => '<p>Filtra por categorías o etiquetas, busca artículos específicos y navega por páginas sin perder contexto.</p>',
                    'empty_message' => 'No hay noticias disponibles por el momento.',
                ],
                'en' => [
                    'intro_title'   => 'Full listing',
                    'intro_text'    => '<p>Filter by categories or tags, search for specific articles, and navigate the results without losing context.</p>',
                    'empty_message' => 'No news available at the moment.',
                ],
            ],
            $langIds
        ));

        $this->cleanupStaleBlocks($newsPageId, $keptInstanceIds);

        echo "✓ News page seeded with collection_listing block\n";
    }

    private function upsertPage(int $collectionId): ?int
    {
        $now = date('Y-m-d H:i:s');

        return $this->upsertRecord('cms_pages', [
            'page_type'     => 'collection_index',
            'collection_id' => $collectionId,
        ], [
            'status'           => 'published',
            'published_at'     => $now,
            'sort_order'       => 1,
            'sitemap_priority' => '0.8',
            'is_in_sitemap'    => 1,
        ]);
    }

    private function collectionIdByKey(string $collectionKey): ?int
    {
        $row = $this->db->table('cms_collections')
            ->select('id')
            ->where('collection_key', $collectionKey)
            ->get()
            ->getRow();

        return $row !== null ? (int) $row->id : null;
    }

    private function upsertPageTranslation(int $pageId, int $languageId, array $translationData): void
    {
        $now = date('Y-m-d H:i:s');

        $this->upsertRecord('cms_page_translations', [
            'page_id'     => $pageId,
            'language_id' => $languageId,
        ], array_merge($translationData, ['created_at' => $now, 'updated_at' => $now]));
    }

    private function upsertBlockWithTranslations(
        int $pageId,
        string $scope,
        array $blockIds,
        string $blockKey,
        int $sortOrder,
        array $config,
        array $labels,
        array $langIds,
        ?int $parentInstanceId = null
    ): ?int {
        $now = date('Y-m-d H:i:s');
        $blockId = $blockIds[$blockKey] ?? null;

        if ($blockId === null) {
            echo "  ⚠️  Block key not found: $blockKey\n";
            return null;
        }

        $instanceId = $this->upsertRecord('cms_block_instances', [
            'block_id'           => $blockId,
            'owner_type'         => $scope,
            'owner_id'           => $pageId,
            'parent_instance_id' => $parentInstanceId,
            'sort_order'         => $sortOrder,
        ], [
            'column_index' => null,
            'is_active'    => 1,
            'block_config' => json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        if ($instanceId === null) {
            return null;
        }

        foreach (['es', 'en'] as $langCode) {
            $langId = $langIds[$langCode] ?? null;
            if ($langId === null) {
                continue;
            }

            $blockData = $labels[$langCode] ?? $labels['es'] ?? [];
            $this->upsertTranslation($instanceId, $langId, $blockData);
        }

        return $instanceId;
    }

    private function trackInstanceId(array &$keptInstanceIds, ?int $instanceId): void
    {
        if ($instanceId !== null) {
            $keptInstanceIds[] = $instanceId;
        }
    }

    private function cleanupStaleBlocks(int $pageId, array $keptInstanceIds): void
    {
        if (empty($keptInstanceIds)) {
            return;
        }

        $this->db->table('cms_block_instances')
            ->where('owner_type', 'page')
            ->where('owner_id', $pageId)
            ->whereNotIn('id', $keptInstanceIds)
            ->delete();
    }

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

    private function upsertTranslation(int $instanceId, int $languageId, array $blockData): void
    {
        $this->upsertRecord('cms_block_instance_translations', [
            'instance_id' => $instanceId,
            'language_id' => $languageId,
        ], [
            'block_data'   => json_encode($blockData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'is_published' => 1,
        ]);
    }
}
