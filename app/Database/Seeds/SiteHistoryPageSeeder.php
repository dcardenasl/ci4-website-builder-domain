<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use App\Database\Seeds\Concerns\IdempotentSeederSupport;
use CodeIgniter\Database\Seeder;

/**
 * Creates the History / Historia page with the following blocks:
 *   page_header, rich_text, image,
 *   metrics_grid (container) + 4 × metric_item children,
 *   rich_text (second section),
 *   accordion (container) + 3 × accordion_item children,
 *   cta.
 *
 * Idempotent: upserts pages, translations, instances, and block translations.
 */
class SiteHistoryPageSeeder extends Seeder
{
    use IdempotentSeederSupport;

    public function run(): void
    {
        $langIds = $this->langIds(['es', 'en']);
        if (! isset($langIds['es'], $langIds['en'])) {
            echo "SiteHistoryPageSeeder: missing languages. Seed CmsLanguageSeeder first.\n";
            return;
        }

        $historyPageId = $this->upsertPage();
        $this->upsertPageTranslation($historyPageId, $langIds['es'], [
            'slug'             => 'historia',
            'title'            => 'Nuestra Historia',
            'excerpt'          => 'Conoce los orígenes y los hitos que nos trajeron hasta aquí.',
            'meta_title'       => 'Nuestra Historia | Mi Sitio',
            'meta_description' => 'Conoce cómo nació nuestra organización y los momentos que marcaron nuestra trayectoria.',
            'canonical_url'    => null,
            'robots'           => 'index, follow',
            'schema_data'      => null,
        ]);
        $this->upsertPageTranslation($historyPageId, $langIds['en'], [
            'slug'             => 'history',
            'title'            => 'Our History',
            'excerpt'          => 'Learn about our origins and the milestones that brought us here.',
            'meta_title'       => 'Our History | My Site',
            'meta_description' => 'Learn how our organization was founded and the moments that shaped our journey.',
            'canonical_url'    => null,
            'robots'           => 'index, follow',
            'schema_data'      => null,
        ]);

        $this->resetPageBlocks($historyPageId);

        $blockIds = $this->blockIds([
            'page_header', 'rich_text', 'image',
            'metrics_grid', 'metric_item',
            'accordion', 'accordion_item',
            'timeline', 'timeline_item',
            'cta',
        ]);

        // ── 1. page_header ────────────────────────────────────────────────────
        $this->upsertBlock(
            $historyPageId,
            $blockIds,
            'page_header',
            1,
            ['bg_color' => 'bg-gray-100', 'css_class' => ''],
            [
                'es' => [
                    'heading'          => 'Nuestra Historia',
                    'subheading'       => 'Un recorrido por los orígenes y hitos que nos definen.',
                    'breadcrumb_label' => 'Inicio',
                    'breadcrumb_url'   => '/',
                ],
                'en' => [
                    'heading'          => 'Our History',
                    'subheading'       => 'A journey through the origins and milestones that define us.',
                    'breadcrumb_label' => 'Home',
                    'breadcrumb_url'   => '/',
                ],
            ],
            $langIds
        );

        // ── 2. rich_text — Introducción ───────────────────────────────────────
        $this->upsertBlock(
            $historyPageId,
            $blockIds,
            'rich_text',
            2,
            ['css_class' => ''],
            [
                'es' => [
                    'content' => '<p>Todo comenzó con una idea sencilla: crear un espacio de encuentro donde las personas pudieran crecer, colaborar y transformar su entorno. Desde nuestros primeros pasos, supimos que el camino sería largo, pero que cada etapa valdría la pena.</p><p>Con el paso de los años, ese sueño inicial fue tomando forma. Lo que empezó como un pequeño proyecto se convirtió en una organización con presencia real, equipos comprometidos y una comunidad que crece día a día.</p>',
                ],
                'en' => [
                    'content' => '<p>It all started with a simple idea: to create a meeting place where people could grow, collaborate, and transform their environment. From our very first steps, we knew the road would be long, but that every stage would be worth it.</p><p>Over the years, that initial dream took shape. What began as a small project became an organization with real presence, committed teams, and a community that grows every day.</p>',
                ],
            ],
            $langIds
        );

        // ── 3. image — Foto histórica ─────────────────────────────────────────
        $this->upsertBlock(
            $historyPageId,
            $blockIds,
            'image',
            3,
            [
                'image'        => $this->mediaReference('https://picsum.photos/id/1015/800/600'),
                'aspect_ratio' => '16/9',
                'css_class'    => '',
            ],
            [
                'es' => [
                    'alt'       => 'Imagen histórica de la organización',
                    'caption'   => 'Los primeros pasos de una historia que sigue escribiéndose.',
                ],
                'en' => [
                    'alt'       => 'Historical image of the organization',
                    'caption'   => 'The first steps of a story that continues to be written.',
                ],
            ],
            $langIds
        );

        // ── 4. metrics_grid (dark) + 4 metric_item children ───────────────────
        $statsSectionId = $this->upsertBlock(
            $historyPageId,
            $blockIds,
            'metrics_grid',
            4,
            ['variant' => 'dark', 'css_class' => ''],
            ['es' => [], 'en' => []],
            $langIds
        );

        $statItems = [
            [
                'sort_order' => 1,
                'es' => ['number' => '2013', 'label' => 'Año de fundación', 'description' => 'Inicio formal del proyecto.', 'source_label' => 'Acta fundacional', 'icon' => 'calendar'],
                'en' => ['number' => '2013', 'label' => 'Year founded',     'description' => 'Formal start of the project.', 'source_label' => 'Founding record', 'icon' => 'calendar'],
            ],
            [
                'sort_order' => 2,
                'es' => ['number' => '10', 'suffix' => '+', 'label' => 'Años de trayectoria',   'description' => 'Experiencia acumulada en comunidad.', 'icon' => 'clock'],
                'en' => ['number' => '10', 'suffix' => '+', 'label' => 'Years of trajectory',   'description' => 'Accumulated community experience.', 'icon' => 'clock'],
            ],
            [
                'sort_order' => 3,
                'es' => ['number' => '50', 'suffix' => '+', 'label' => 'Proyectos realizados',  'description' => 'Iniciativas, pilotos y programas.', 'icon' => 'briefcase'],
                'en' => ['number' => '50', 'suffix' => '+', 'label' => 'Projects completed',    'description' => 'Initiatives, pilots, and programs.', 'icon' => 'briefcase'],
            ],
            [
                'sort_order' => 4,
                'es' => ['number' => '500', 'suffix' => '+', 'label' => 'Personas en comunidad', 'description' => 'Red activa de participantes.', 'icon' => 'users'],
                'en' => ['number' => '500', 'suffix' => '+', 'label' => 'People in community',   'description' => 'Active participant network.', 'icon' => 'users'],
            ],
        ];
        $this->seedChildBlocks($historyPageId, $statsSectionId, 'metric_item', $statItems, $blockIds, $langIds);

        // ── 5. rich_text — Continuación ───────────────────────────────────────
        $this->upsertBlock(
            $historyPageId,
            $blockIds,
            'rich_text',
            5,
            ['css_class' => ''],
            [
                'es' => [
                    'content' => '<h2>Una trayectoria marcada por el aprendizaje</h2><p>En cada etapa encontramos nuevos desafíos que nos obligaron a reinventarnos. Aprendimos a escuchar a nuestra comunidad, a ajustar el rumbo cuando era necesario y a celebrar cada logro, por pequeño que fuera.</p><p>Hoy miramos hacia atrás con orgullo y hacia adelante con la misma energía que nos movió desde el primer día. Nuestra historia no es solo nuestra — es de todos los que nos han acompañado en este camino.</p>',
                ],
                'en' => [
                    'content' => '<h2>A journey marked by learning</h2><p>At every stage we found new challenges that forced us to reinvent ourselves. We learned to listen to our community, to adjust course when necessary, and to celebrate every achievement, no matter how small.</p><p>Today we look back with pride and forward with the same energy that moved us from day one. Our history is not just ours — it belongs to everyone who has walked this path with us.</p>',
                ],
            ],
            $langIds
        );

        // ── 6. accordion + 3 accordion_item children ────────────────────────────
        $faqAccordionId = $this->upsertBlock(
            $historyPageId,
            $blockIds,
            'accordion',
            6,
            ['css_class' => ''],
            ['es' => [], 'en' => []],
            $langIds
        );

        $faqItems = [
            [
                'sort_order' => 1,
                'is_open'    => true,
                'es' => [
                    'title'    => '¿Cómo nació la organización?',
                    'content'  => '<p>La organización nació en 2013 de la iniciativa de un grupo de personas con una visión común: construir comunidad desde la acción. Lo que comenzó como reuniones informales se transformó rápidamente en un proyecto estructurado con objetivos claros.</p>',
                ],
                'en' => [
                    'title'    => 'How was the organization founded?',
                    'content'  => '<p>The organization was founded in 2013 by a group of people with a common vision: to build community through action. What began as informal meetings quickly became a structured project with clear objectives.</p>',
                ],
            ],
            [
                'sort_order' => 2,
                'is_open'    => false,
                'es' => [
                    'title'    => '¿Cuáles han sido los hitos más importantes?',
                    'content'  => '<p>Entre los momentos clave de nuestra historia están la obtención de nuestro primer financiamiento institucional en 2015, la apertura de nuestra segunda sede en 2018, y la consolidación de nuestra plataforma digital en 2022, que nos permitió llegar a más personas que nunca.</p>',
                ],
                'en' => [
                    'title'    => 'What have been the most important milestones?',
                    'content'  => '<p>Among the key moments in our history are obtaining our first institutional funding in 2015, opening our second location in 2018, and consolidating our digital platform in 2022, which allowed us to reach more people than ever before.</p>',
                ],
            ],
            [
                'sort_order' => 3,
                'is_open'    => false,
                'es' => [
                    'title'    => '¿Hacia dónde va la organización?',
                    'content'  => '<p>Nuestro plan para los próximos años contempla la expansión a nuevas regiones, el fortalecimiento de nuestros programas de formación y la construcción de alianzas estratégicas con otras organizaciones. Queremos seguir creciendo sin perder el espíritu que nos trajo hasta aquí.</p>',
                ],
                'en' => [
                    'title'    => 'Where is the organization headed?',
                    'content'  => '<p>Our plan for the coming years includes expanding to new regions, strengthening our training programs, and building strategic alliances with other organizations. We want to keep growing without losing the spirit that brought us here.</p>',
                ],
            ],
        ];

        foreach ($faqItems as $faqItem) {
            $blockId = $blockIds['accordion_item'] ?? null;
            if ($blockId === null) {
                continue;
            }

            $instanceId = $this->upsertRecord('cms_block_instances', [
                'block_id'           => $blockId,
                'owner_type'         => 'page',
                'owner_id'           => $historyPageId,
                'parent_instance_id' => $faqAccordionId,
                'sort_order'         => (int) $faqItem['sort_order'],
            ], [
                'column_index' => null,
                'is_active'    => 1,
                'block_config'  => json_encode(['is_open' => $faqItem['is_open']], JSON_UNESCAPED_UNICODE),
            ]);

            if ($instanceId === null) {
                continue;
            }

            foreach (['es', 'en'] as $lang) {
                $langId = $langIds[$lang] ?? null;
                if ($langId === null || ! isset($faqItem[$lang])) {
                    continue;
                }
                $this->upsertTranslation($instanceId, $langId, $faqItem[$lang]);
            }
        }

        // ── 7. timeline ───────────────────────────────────────────────────────
        $timelineInstanceId = $this->upsertBlock(
            $historyPageId,
            $blockIds,
            'timeline',
            7,
            ['layout' => 'alternating', 'css_class' => 'bg-slate-50/50'],
            [
                'es' => [
                    'section_title' => 'Nuestra Trayectoria en Detalle',
                    'description'   => 'Un repaso cronológico de los momentos clave que definieron nuestro camino.',
                ],
                'en' => [
                    'section_title' => 'Our Journey in Detail',
                    'description'   => 'A chronological review of the key moments that defined our path.',
                ],
            ],
            $langIds
        );

        $timelineItems = [
            [
                'sort_order' => 1,
                'es' => [
                    'date_label'  => '2018',
                    'title'       => 'El Nacimiento del Proyecto',
                    'description' => '<p>Comenzamos en un pequeño garaje con solo 3 personas llenas de ilusión. Nuestro primer objetivo fue crear una base tecnológica sólida.</p>',
                    'link_url'    => '/contacto',
                    'link_label'  => 'Contactar fundadores',
                ],
                'en' => [
                    'date_label'  => '2018',
                    'title'       => 'The Birth of the Project',
                    'description' => '<p>We started in a small garage with only 3 passionate people. Our primary goal was to build a strong technological baseline.</p>',
                    'link_url'    => '/contact',
                    'link_label'  => 'Contact founders',
                ],
            ],
            [
                'sort_order' => 2,
                'es' => [
                    'date_label'  => '2021',
                    'title'       => 'Expansión Regional',
                    'description' => '<p>Logramos presencia en 4 países de Latinoamérica, aumentando nuestro impacto y adaptando nuestras soluciones a diferentes mercados.</p>',
                ],
                'en' => [
                    'date_label'  => '2021',
                    'title'       => 'Regional Expansion',
                    'description' => '<p>We achieved presence in 4 Latin American countries, increasing our impact and tailoring our solutions to different markets.</p>',
                ],
            ],
            [
                'sort_order' => 3,
                'es' => [
                    'date_label'  => '2025',
                    'title'       => 'Digitalización Completa',
                    'description' => '<p>Lanzamos nuestra plataforma cloud descentralizada, logrando automatizar el 95% de los procesos editoriales para todos nuestros clientes.</p>',
                ],
                'en' => [
                    'date_label'  => '2025',
                    'title'       => 'Full Digitalization',
                    'description' => '<p>We launched our decentralized cloud platform, successfully automating 95% of content publishing processes for our users.</p>',
                ],
            ],
        ];

        $this->seedChildBlocks($historyPageId, $timelineInstanceId, 'timeline_item', $timelineItems, $blockIds, $langIds);

        // ── 8. cta ────────────────────────────────────────────────────────────
        $this->upsertBlock(
            $historyPageId,
            $blockIds,
            'cta',
            8,
            ['variant' => 'blue', 'css_class' => ''],
            [
                'es' => [
                    'heading' => '¿Quieres ser parte de nuestra historia?',
                    'text'    => 'Escríbenos y cuéntanos cómo puedes sumarte. Siempre hay un lugar para quienes quieren aportar.',
                    'label'   => 'Contáctanos',
                    'url'     => '/contacto',
                ],
                'en' => [
                    'heading' => 'Want to be part of our story?',
                    'text'    => 'Write to us and tell us how you can join. There is always room for those who want to contribute.',
                    'label'   => 'Contact us',
                    'url'     => '/contact',
                ],
            ],
            $langIds
        );
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function upsertPage(): int
    {
        $existing = $this->db->table('cms_pages')
            ->select('cms_pages.id')
            ->join('cms_page_translations', 'cms_page_translations.page_id = cms_pages.id')
            ->whereIn('cms_page_translations.slug', ['historia', 'history'])
            ->where('cms_pages.deleted_at IS NULL', null, false)
            ->orderBy('cms_pages.id', 'ASC')
            ->get()->getRowArray();
        $data = [
            'page_type'          => 'generic',
            'status'             => 'published',
            'published_at'       => date('Y-m-d H:i:s'),
            'scheduled_at'       => null,
            'sort_order'         => 35,
            'sitemap_priority'   => '0.6',
            'sitemap_changefreq' => 'yearly',
            'is_in_sitemap'      => 1,
            'deleted_at'         => null,
        ];
        if ($existing !== null) {
            $pageId = (int) $existing['id'];
            $this->db->table('cms_pages')->where('id', $pageId)->update($data);
        } else {
            $pageId = $this->createRecord('cms_pages', $data);
        }

        if ($pageId === null) {
            throw new \RuntimeException('SiteHistoryPageSeeder: unable to seed history page.');
        }

        return $pageId;
    }

    /** @param array<string, mixed> $translationData */
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
     * Upsert a top-level block instance and its translations. Returns the instance id.
     *
     * @param array<string, int>                  $blockIds
     * @param array<string, mixed>                $config
     * @param array<string, array<string, mixed>> $translations
     * @param array<string, int>                  $langIds
     */
    private function upsertBlock(
        int    $pageId,
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
            echo "SiteHistoryPageSeeder: block type '{$blockKey}' not found — skipped.\n";
            return 0;
        }

        $instanceId = $this->upsertRecord('cms_block_instances', [
            'block_id'           => $blockId,
            'owner_type'         => 'page',
            'owner_id'           => $pageId,
            'parent_instance_id' => $parentInstanceId,
            'sort_order'         => $sortOrder,
        ], [
            'column_index'       => null,
            'is_active'          => 1,
            'block_config'       => json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
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
     * @param array<int, array<string, mixed>> $items
     * @param array<string, int>               $blockIds
     * @param array<string, int>               $langIds
     */
    private function seedChildBlocks(int $pageId, int $parentInstanceId, string $blockKey, array $items, array $blockIds, array $langIds): void
    {
        $blockId = $blockIds[$blockKey] ?? null;
        if ($blockId === null) {
            return;
        }

        foreach ($items as $item) {
            $instanceId = $this->upsertRecord('cms_block_instances', [
                'block_id'           => $blockId,
                'owner_type'         => 'page',
                'owner_id'           => $pageId,
                'parent_instance_id' => $parentInstanceId,
                'sort_order'         => (int) $item['sort_order'],
            ], [
                'column_index'       => null,
                'is_active'          => 1,
                'block_config'       => json_encode([], JSON_UNESCAPED_UNICODE),
            ]);

            foreach (['es', 'en'] as $lang) {
                $langId = $langIds[$lang] ?? null;
                if ($langId === null || ! isset($item[$lang])) {
                    continue;
                }
                $this->upsertTranslation($instanceId, $langId, $item[$lang]);
            }
        }
    }

    /** @param string[] $keys  @return array<string, int> */
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

    /** @param array<string, mixed> $blockData */
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
