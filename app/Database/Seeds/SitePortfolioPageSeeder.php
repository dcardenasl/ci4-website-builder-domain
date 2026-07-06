<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Creates the Portfolio / Portafolio page and seeds the following blocks:
 *   page_header, collection_grid, image, alert, tabs, gallery.
 *
 * Idempotent: upserts the page, its translations, block instances,
 * and block translations.
 */
class SitePortfolioPageSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CmsLanguageSeeder::class);
        $this->call(CmsBlockTypeSeeder::class);

        $langIds = $this->langIds(['es', 'en']);
        if (! isset($langIds['es'], $langIds['en'])) {
            echo "SitePortfolioPageSeeder: missing languages. Seed CmsLanguageSeeder first.\n";
            return;
        }

        $portfolioPageId = $this->upsertPage();
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

        $blockIds = $this->blockIds(['page_header', 'collection_grid', 'image', 'alert', 'tabs', 'tab_item', 'gallery', 'gallery_item']);
        $this->resetPortfolioBlocks($portfolioPageId);

        // ── 1. page_header ────────────────────────────────────────────────────
        $this->upsertBlockWithTranslations(
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
        );

        // ── 2. collection_grid ─────────────────────────────────────────────────
        $this->upsertBlockWithTranslations(
            $portfolioPageId,
            'page',
            $blockIds,
            'collection_grid',
            2,
            [
                'collection_key'  => 'portafolio',
                'items_limit'     => 6,
                'order_by'        => 'sort_order',
                'order_direction' => 'asc',
                'layout_variant'  => 'portfolio',
                'css_class'       => '',
            ],
            [
                'es' => [
                    'section_title'    => 'Proyectos Destacados',
                    'section_subtitle' => 'Diseños y desarrollos a medida realizados con pasión.',
                    'view_all_label'   => 'Ver todos los proyectos',
                    'view_all_url'     => '/portafolio',
                    'empty_message'    => 'No hay proyectos en el portafolio por el momento.',
                ],
                'en' => [
                    'section_title'    => 'Featured Projects',
                    'section_subtitle' => 'Custom design and development made with passion.',
                    'view_all_label'   => 'View all projects',
                    'view_all_url'     => '/portfolio',
                    'empty_message'    => 'No projects in the portfolio at the moment.',
                ],
            ],
            $langIds
        );

        // ── 3. image (standalone banner) ───────────────────────────────────────
        $this->upsertBlockWithTranslations(
            $portfolioPageId,
            'page',
            $blockIds,
            'image',
            3,
            ['aspect_ratio' => '16/9', 'css_class' => ''],
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
        );

        // ── 4. alert (important note) ──────────────────────────────────────────
        $this->upsertBlockWithTranslations(
            $portfolioPageId,
            'page',
            $blockIds,
            'alert',
            4,
            ['alert_type' => 'info', 'dismissible' => true, 'css_class' => 'my-8'],
            [
                'es' => [
                    'title'   => 'Nota de Calidad',
                    'content' => '<p>Todos los proyectos presentados a continuación representan soluciones a la medida y casos reales de éxito para nuestros clientes. Los detalles técnicos están actualizados al año corriente.</p>',
                ],
                'en' => [
                    'title'   => 'Quality Note',
                    'content' => '<p>All projects presented below represent custom-tailored solutions and real-world client success stories. Technical details are updated to the current year.</p>',
                ],
            ],
            $langIds
        );

        // ── 5. tabs (methodology & technologies) ────────────────────────────────
        $tabsInstanceId = $this->upsertBlockWithTranslations(
            $portfolioPageId,
            'page',
            $blockIds,
            'tabs',
            5,
            ['layout' => 'horizontal', 'css_class' => 'my-12'],
            ['es' => [], 'en' => []],
            $langIds
        );

        if ($tabsInstanceId > 0) {
            // Tab item 1: Methodology
            $this->upsertBlockWithTranslations(
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
            );

            // Tab item 2: Technologies
            $this->upsertBlockWithTranslations(
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
            );
        }

        // ── 6. gallery (mosaico de capturas de proyectos) ───────────────────────
        $galleryInstanceId = $this->upsertBlockWithTranslations(
            $portfolioPageId,
            'page',
            $blockIds,
            'gallery',
            6,
            ['columns' => '3', 'gap' => 'gap-6', 'css_class' => 'my-16'],
            ['es' => [], 'en' => []],
            $langIds
        );

        if ($galleryInstanceId > 0) {
            // Gallery item 1: Dashboard UI
            $this->upsertBlockWithTranslations(
                $portfolioPageId,
                'page',
                $blockIds,
                'gallery_item',
                1,
                ['image_url' => 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?auto=format&fit=crop&w=600&q=80', 'css_class' => ''],
                [
                    'es' => [
                        'alt'     => 'Panel de Control de Analítica',
                        'caption' => 'Visualización de datos avanzados y monitoreo en tiempo real.',
                    ],
                    'en' => [
                        'alt'     => 'Analytics Dashboard Control Panel',
                        'caption' => 'Advanced data visualization and real-time monitoring.',
                    ],
                ],
                $langIds,
                $galleryInstanceId
            );

            // Gallery item 2: Responsive UI Design
            $this->upsertBlockWithTranslations(
                $portfolioPageId,
                'page',
                $blockIds,
                'gallery_item',
                2,
                ['image_url' => 'https://images.unsplash.com/photo-1507238691740-187a5b1d37b8?auto=format&fit=crop&w=600&q=80', 'css_class' => ''],
                [
                    'es' => [
                        'alt'     => 'Diseño UI Adaptable para Tablet',
                        'caption' => 'Interfaces optimizadas para ofrecer una navegación impecable en dispositivos móviles.',
                    ],
                    'en' => [
                        'alt'     => 'Adaptive UI Design for Tablet',
                        'caption' => 'Optimized interfaces delivering seamless navigation across mobile devices.',
                    ],
                ],
                $langIds,
                $galleryInstanceId
            );

            // Gallery item 3: E-commerce Architecture
            $this->upsertBlockWithTranslations(
                $portfolioPageId,
                'page',
                $blockIds,
                'gallery_item',
                3,
                ['image_url' => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=600&q=80', 'css_class' => ''],
                [
                    'es' => [
                        'alt'     => 'Arquitectura de Comercio Electrónico',
                        'caption' => 'Catálogos dinámicos auto-administrables y pasarelas de cobro completamente seguras.',
                    ],
                    'en' => [
                        'alt'     => 'E-commerce Architecture Design',
                        'caption' => 'Self-managed dynamic catalogs and completely secure payment gateways.',
                    ],
                ],
                $langIds,
                $galleryInstanceId
            );
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function upsertPage(): int
    {
        $existing = $this->db->table('cms_pages')
            ->where('page_type', 'portfolio')
            ->where('deleted_at IS NULL', null, false)
            ->get()
            ->getRowArray();

        $payload = [
            'page_type'          => 'portfolio',
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

    private function resetPortfolioBlocks(int $pageId): void
    {
        $instanceIds = $this->db->table('cms_block_instances')
            ->select('id')
            ->where('owner_type', 'page')
            ->where('owner_id', $pageId)
            ->where('parent_instance_id IS NULL', null, false)
            ->get()
            ->getResultArray();

        if ($instanceIds === []) {
            return;
        }

        $ids = array_map(static fn (array $row): int => (int) $row['id'], $instanceIds);
        $this->db->table('cms_block_instance_translations')->whereIn('instance_id', $ids)->delete();
        $this->db->table('cms_block_instances')->whereIn('id', $ids)->delete();
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

        $query = $this->db->table('cms_block_instances')
            ->where('block_id', $blockId)
            ->where('owner_type', $ownerType)
            ->where('owner_id', $pageId)
            ->where('sort_order', $sortOrder);

        if ($parentInstanceId !== null) {
            $query->where('parent_instance_id', $parentInstanceId);
        } else {
            $query->where('parent_instance_id IS NULL', null, false);
        }

        $existing = $query->get()->getRowArray();

        $payload = [
            'block_id'           => $blockId,
            'owner_type'         => $ownerType,
            'owner_id'           => $pageId,
            'parent_instance_id' => $parentInstanceId,
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
