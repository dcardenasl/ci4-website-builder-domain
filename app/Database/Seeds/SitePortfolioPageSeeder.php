<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use App\Database\Seeds\Concerns\IdempotentSeederSupport;
use CodeIgniter\Database\Seeder;

/**
 * Creates the Portfolio / Portafolio collection index page and seeds the following blocks:
 *   page_header, rich_text, collection_listing, image, alert, tabs.
 *
 * Idempotent: upserts the page, its translations, block instances,
 * and block translations.
 */
class SitePortfolioPageSeeder extends Seeder
{
    use IdempotentSeederSupport;

    public function run(): void
    {
        $langIds = $this->langIds(['es', 'en']);
        if (! isset($langIds['es'], $langIds['en'])) {
            echo "SitePortfolioPageSeeder: missing languages. Seed CmsLanguageSeeder first.\n";
            return;
        }

        $collectionId = $this->collectionIdByKey('portafolio');
        if ($collectionId === null) {
            echo "SitePortfolioPageSeeder: portfolio collection not found. Seed PortfolioCollectionSeeder first.\n";
            return;
        }

        $portfolioPageId = $this->upsertPage($collectionId);
        $this->upsertPageTranslation($portfolioPageId, $langIds['es'], [
            'slug'             => 'portafolio',
            'title'            => 'Portafolio',
            'excerpt'          => 'Explora nuestros trabajos recientes y casos de éxito.',
            'meta_title'       => 'Portafolio | Mi Sitio',
            'meta_description' => 'Explora nuestros trabajos recientes y casos de éxito.',
            'canonical_url'    => null,
            'robots'           => 'index, follow',
            'schema_data'      => null,
        ]);
        $this->upsertPageTranslation($portfolioPageId, $langIds['en'], [
            'slug'             => 'portfolio',
            'title'            => 'Portfolio',
            'excerpt'          => 'Explore our recent works and success stories.',
            'meta_title'       => 'Portfolio | My Site',
            'meta_description' => 'Explore our recent works and success stories.',
            'canonical_url'    => null,
            'robots'           => 'index, follow',
            'schema_data'      => null,
        ]);

        $blockIds = $this->blockIds(['page_header', 'collection_listing', 'rich_text', 'image', 'alert', 'tabs', 'tab_item']);
        $keptInstanceIds = [];

        // ── 1. page_header ────────────────────────────────────────────────────
        $this->trackInstanceId($keptInstanceIds, $this->upsertBlockWithTranslations(
            $portfolioPageId,
            'page',
            $blockIds,
            'page_header',
            1,
            ['bg_color' => 'bg-gray-100', 'css_class' => ''],
            [
                'es' => [
                    'heading'          => 'Portafolio',
                    'subheading'       => 'Explora nuestros trabajos recientes.',
                    'breadcrumb_label' => 'Inicio',
                    'breadcrumb_url'   => '/',
                ],
                'en' => [
                    'heading'          => 'Portfolio',
                    'subheading'       => 'Explore our recent works.',
                    'breadcrumb_label' => 'Home',
                    'breadcrumb_url'   => '/',
                ],
            ],
            $langIds
        ));

        // ── 2. rich_text intro ─────────────────────────────────────────────────
        $this->trackInstanceId($keptInstanceIds, $this->upsertBlockWithTranslations(
            $portfolioPageId,
            'page',
            $blockIds,
            'rich_text',
            2,
            [],
            [
                'es' => [
                    'content' => '<p>Explora una selección curada de proyectos y casos de estudio que muestran nuestro enfoque, el proceso y el resultado final.</p>',
                ],
                'en' => [
                    'content' => '<p>Explore a curated selection of projects and case studies that showcase our approach, process, and final outcomes.</p>',
                ],
            ],
            $langIds
        ));

        // ── 3. collection_listing ─────────────────────────────────────────────
        $this->trackInstanceId($keptInstanceIds, $this->upsertBlockWithTranslations(
            $portfolioPageId,
            'page',
            $blockIds,
            'collection_listing',
            3,
            [
                'collection_id'    => $collectionId,
                'per_page'         => 12,
                'order_by'         => 'published_at',
                'order_direction'  => 'desc',
                'layout_variant'   => 'portfolio',
                'show_search'      => 1,
                'show_categories'  => 1,
                'show_tags'        => 1,
                'css_class'        => '',
            ],
            [
                'es' => [
                    'intro_title'   => 'Listado completo',
                    'intro_text'    => '<p>Filtra por categorías o etiquetas, busca proyectos específicos y navega por páginas sin perder contexto.</p>',
                    'empty_message' => 'No hay proyectos disponibles por el momento.',
                ],
                'en' => [
                    'intro_title'   => 'Full listing',
                    'intro_text'    => '<p>Filter by categories or tags, search for specific projects, and navigate the results without losing context.</p>',
                    'empty_message' => 'No projects available at the moment.',
                ],
            ],
            $langIds
        ));

        // ── 4. image (standalone banner) ───────────────────────────────────────
        $this->trackInstanceId($keptInstanceIds, $this->upsertBlockWithTranslations(
            $portfolioPageId,
            'page',
            $blockIds,
            'image',
            4,
            [
                'image'        => $this->mediaReference('https://picsum.photos/id/355/1200/675'),
                'aspect_ratio' => '16/9',
                'css_class'    => '',
            ],
            [
                'es' => [
                    'alt'     => 'Imagen de la sección de portafolio',
                    'caption' => 'Construimos el futuro digital de nuestros clientes.',
                ],
                'en' => [
                    'alt'     => 'Portfolio section image',
                    'caption' => 'Building the digital future of our clients.',
                ],
            ],
            $langIds
        ));

        // ── 5. alert (important note) ──────────────────────────────────────────
        $this->trackInstanceId($keptInstanceIds, $this->upsertBlockWithTranslations(
            $portfolioPageId,
            'page',
            $blockIds,
            'alert',
            5,
            ['type' => 'info', 'dismissible' => true, 'css_class' => 'my-8'],
            [
                'es' => [
                    'title'   => 'Nota de Calidad',
                    'message' => '<p>Todos los proyectos presentados a continuación representan soluciones a la medida y casos reales de éxito para nuestros clientes. Los detalles técnicos están actualizados al año corriente.</p>',
                ],
                'en' => [
                    'title'   => 'Quality Note',
                    'message' => '<p>All projects presented below represent custom-tailored solutions and real-world client success stories. Technical details are updated to the current year.</p>',
                ],
            ],
            $langIds
        ));

        // ── 6. tabs (methodology & technologies) ────────────────────────────────
        $tabsInstanceId = $this->upsertBlockWithTranslations(
            $portfolioPageId,
            'page',
            $blockIds,
            'tabs',
            6,
            ['layout' => 'horizontal', 'css_class' => 'my-12'],
            ['es' => [], 'en' => []],
            $langIds
        );
        $this->trackInstanceId($keptInstanceIds, $tabsInstanceId);

        if ($tabsInstanceId > 0) {
            // Tab item 1: Methodology
            $this->trackInstanceId($keptInstanceIds, $this->upsertBlockWithTranslations(
                $portfolioPageId,
                'page',
                $blockIds,
                'tab_item',
                1,
                [],
                [
                    'es' => [
                        'title'   => 'Metodología',
                        'content' => '<h3 class="text-xl font-bold mb-2">Diseño Centrado en el Usuario</h3><p class="text-slate-600">Nuestra metodología de desarrollo sitúa al usuario final en el centro de cada etapa de toma de decisiones. Llevamos a cabo prototipados rápidos, pruebas A/B de flujos clave y validaciones de usabilidad iterativas para garantizar que cada aplicación sea intuitiva, veloz y sumamente fácil de operar.</p>',
                    ],
                    'en' => [
                        'title'   => 'Methodology',
                        'content' => '<h3 class="text-xl font-bold mb-2">User-Centered Design</h3><p class="text-slate-600">Our development methodology places the end-user at the center of every stage of the decision-making process. We conduct rapid prototyping, A/B testing of key user flows, and iterative usability validations to ensure that every application is intuitive, fast, and extremely easy to operate.</p>',
                    ],
                ],
                $langIds,
                $tabsInstanceId
            ));

            // Tab item 2: Technologies
            $this->trackInstanceId($keptInstanceIds, $this->upsertBlockWithTranslations(
                $portfolioPageId,
                'page',
                $blockIds,
                'tab_item',
                2,
                [],
                [
                    'es' => [
                        'title'   => 'Tecnologías',
                        'content' => '<h3 class="text-xl font-bold mb-2">Stack Tecnológico Moderno</h3><p class="text-slate-600">Implementamos soluciones de alto rendimiento utilizando un stack tecnológico moderno, maduro y robusto. Nos apoyamos en PHP 8.2+, CodeIgniter 4, bases de datos relacionales indexadas con precisión, integraciones seguras de pasarelas de pago y estilos dinámicos a través de Tailwind CSS y Alpine.js.</p>',
                    ],
                    'en' => [
                        'title'   => 'Technologies',
                        'content' => '<h3 class="text-xl font-bold mb-2">Modern Tech Stack</h3><p class="text-slate-600">We deploy high-performance solutions using a modern, mature, and robust tech stack. We leverage PHP 8.2+, CodeIgniter 4, precisely indexed relational databases, secure payment gateway integrations, and dynamic layouts styled through Tailwind CSS and Alpine.js.</p>',
                    ],
                ],
                $langIds,
                $tabsInstanceId
            ));
        }

        $this->cleanupStalePortfolioBlocks($portfolioPageId, $keptInstanceIds);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function upsertPage(int $collectionId): ?int
    {
        return $this->upsertCollectionIndexPageRecord($collectionId, [
            'status'             => 'published',
            'published_at'       => date('Y-m-d H:i:s'),
            'scheduled_at'       => null,
            'sort_order'         => 40,
            'sitemap_priority'   => '0.8',
            'sitemap_changefreq' => 'weekly',
            'is_in_sitemap'      => 1,
            'deleted_at'         => null,
        ]);
    }

    private function collectionIdByKey(string $collectionKey): ?int
    {
        $row = $this->db->table('cms_collections')
            ->where('collection_key', $collectionKey)
            ->get()
            ->getRowArray();

        return is_array($row) ? (int) $row['id'] : null;
    }

    /**
     * @param array<string, mixed> $translationData
     */
    private function upsertPageTranslation(int $pageId, int $languageId, array $translationData): void
    {
        $slug = (string) ($translationData['slug'] ?? '');
        if ($slug !== '') {
            $conflict = $this->db->table('cms_page_translations')
                ->where('language_id', $languageId)
                ->where('slug', $slug)
                ->get()
                ->getRowArray();
            if ($conflict !== null && (int) $conflict['page_id'] !== $pageId) {
                return;
            }
        }

        $this->upsertRecord('cms_page_translations', [
            'page_id'     => $pageId,
            'language_id' => $languageId,
        ], $translationData);
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
        array  $langIds,
        ?int   $parentInstanceId = null
    ): int {
        $blockId = $blockIds[$blockKey] ?? null;
        if ($blockId === null) {
            echo "SitePortfolioPageSeeder: block type '{$blockKey}' not found — skipped.\n";
            return 0;
        }

        $instanceId = $this->upsertRecord('cms_block_instances', [
            'block_id'           => $blockId,
            'owner_type'         => $ownerType,
            'owner_id'           => $pageId,
            'parent_instance_id' => $parentInstanceId,
            'sort_order'         => $sortOrder,
        ], [
            'column_index' => null,
            'is_active'    => 1,
            'block_config'  => json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

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
     * @param array<int, int> $keptInstanceIds
     */
    private function trackInstanceId(array &$keptInstanceIds, int $instanceId): void
    {
        if ($instanceId > 0) {
            $keptInstanceIds[$instanceId] = $instanceId;
        }
    }

    /**
     * Remove stale block instances only after the desired tree has been synced.
     *
     * @param array<int, int> $keptInstanceIds
     */
    private function cleanupStalePortfolioBlocks(int $pageId, array $keptInstanceIds): void
    {
        $rows = $this->db->table('cms_block_instances')
            ->select('id')
            ->where('owner_type', 'page')
            ->where('owner_id', $pageId)
            ->get()
            ->getResultArray();

        if ($rows === []) {
            return;
        }

        $keepIds = array_values($keptInstanceIds);
        $staleIds = [];
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            if (! in_array($id, $keepIds, true)) {
                $staleIds[] = $id;
            }
        }

        if ($staleIds === []) {
            return;
        }

        $this->db->table('cms_block_instance_translations')->whereIn('instance_id', $staleIds)->delete();
        $this->db->table('cms_block_instances')->whereIn('id', $staleIds)->delete();
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
        $this->upsertRecord('cms_block_instance_translations', [
            'instance_id' => $instanceId,
            'language_id' => $languageId,
        ], [
            'block_data'   => json_encode($blockData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'is_published' => 1,
        ]);
    }
}
