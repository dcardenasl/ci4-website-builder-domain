<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Creates the About / Quiénes Somos page with the following blocks:
 *   page_header, hero_banner, rich_text,
 *   cards_grid (container) + 3 × card_item (Misión, Visión, Valores),
 *   metrics_grid (container) + 3 × metric_item,
 *   cards_slider (container) + 2 × slide_card,
 *   asset_showcase (container) + 3 × asset_item,
 *   accordion (container) + 3 × accordion_item,
 *   video_player.
 *
 * Idempotent: upserts pages, translations, instances, and block translations.
 */
class SiteAboutPageSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CmsLanguageSeeder::class);
        $this->call(CmsBlockTypeSeeder::class);

        $langIds = $this->langIds(['es', 'en']);
        if (! isset($langIds['es'], $langIds['en'])) {
            echo "SiteAboutPageSeeder: missing languages. Seed CmsLanguageSeeder first.\n";
            return;
        }

        $aboutPageId = $this->upsertPage();
        $this->upsertPageTranslation($aboutPageId, $langIds['es'], [
            'slug'             => 'nosotros',
            'title'            => 'Quiénes Somos',
            'excerpt'          => 'Conoce nuestra misión, visión, valores y el equipo que hace posible todo esto.',
            'meta_title'       => 'Quiénes Somos | Mi Sitio',
            'meta_description' => 'Descubre la misión, visión y valores que guían nuestra organización.',
            'canonical_url'    => null,
            'robots'           => 'index, follow',
            'schema_data'      => null,
        ]);
        $this->upsertPageTranslation($aboutPageId, $langIds['en'], [
            'slug'             => 'about',
            'title'            => 'About Us',
            'excerpt'          => 'Learn about our mission, vision, values, and the team that makes it all possible.',
            'meta_title'       => 'About Us | My Site',
            'meta_description' => 'Discover the mission, vision, and values that guide our organization.',
            'canonical_url'    => null,
            'robots'           => 'index, follow',
            'schema_data'      => null,
        ]);

        $blockIds = $this->blockIds([
            'page_header', 'hero_banner', 'rich_text',
            'cards_grid', 'card_item',
            'metrics_grid', 'metric_item',
            'cards_slider', 'slide_card',
            'asset_showcase', 'asset_item',
            'accordion', 'accordion_item',
            'video_player',
        ]);

        // ── 1. page_header ────────────────────────────────────────────────────
        $this->upsertBlock(
            $aboutPageId,
            $blockIds,
            'page_header',
            1,
            ['bg_color' => 'bg-gray-100', 'css_class' => ''],
            [
                'es' => [
                    'heading'          => 'Quiénes Somos',
                    'subheading'       => 'La misión, visión y valores que nos guían cada día.',
                    'breadcrumb_label' => 'Inicio',
                    'breadcrumb_url'   => '/',
                ],
                'en' => [
                    'heading'          => 'About Us',
                    'subheading'       => 'The mission, vision, and values that guide us every day.',
                    'breadcrumb_label' => 'Home',
                    'breadcrumb_url'   => '/',
                ],
            ],
            $langIds
        );

        // ── 2. hero_banner ────────────────────────────────────────────────────
        $this->upsertBlock(
            $aboutPageId,
            $blockIds,
            'hero_banner',
            2,
            ['css_class' => ''],
            [
                'es' => [
                    'heading'    => 'Nuestra misión nos une',
                    'subheading' => 'Trabajamos para crear espacios de encuentro, aprendizaje y transformación que impacten positivamente a nuestra comunidad.',
                    'cta_label'  => 'Conoce nuestra historia',
                    'cta_url'    => '/historia',
                ],
                'en' => [
                    'heading'    => 'Our mission brings us together',
                    'subheading' => 'We work to create spaces of encounter, learning, and transformation that positively impact our community.',
                    'cta_label'  => 'Learn our history',
                    'cta_url'    => '/history',
                ],
            ],
            $langIds
        );

        // ── 3. rich_text ──────────────────────────────────────────────────────
        $this->upsertBlock(
            $aboutPageId,
            $blockIds,
            'rich_text',
            3,
            ['css_class' => ''],
            [
                'es' => [
                    'content' => '<p>Somos una organización comprometida con el desarrollo de las personas y las comunidades. Creemos que el cambio real surge cuando la gente se junta con un propósito compartido, con las herramientas adecuadas y con el apoyo necesario para avanzar.</p><p>Nuestro equipo está formado por profesionales apasionados que trabajan día a día para que cada proyecto, cada programa y cada iniciativa genere un impacto duradero.</p>',
                ],
                'en' => [
                    'content' => '<p>We are an organization committed to the development of people and communities. We believe that real change happens when people come together with a shared purpose, the right tools, and the support needed to move forward.</p><p>Our team is made up of passionate professionals who work day in and day out to ensure that every project, every program, and every initiative generates a lasting impact.</p>',
                ],
            ],
            $langIds
        );

        // ── 4. cards_grid (Misión / Visión / Valores) ─────────────────────
        $featuresGridId = $this->upsertBlock(
            $aboutPageId,
            $blockIds,
            'cards_grid',
            4,
            ['columns_desktop' => '3', 'variant' => 'bordered', 'css_class' => ''],
            ['es' => [], 'en' => []],
            $langIds
        );

        $featureCards = [
            [
                'sort_order' => 1,
                'es' => [
                    'title'       => 'Misión',
                    'description' => 'Crear oportunidades de crecimiento, aprendizaje y transformación para las personas y comunidades con las que trabajamos.',
                    'link_url'    => '',
                    'link_label'  => '',
                ],
                'en' => [
                    'title'       => 'Mission',
                    'description' => 'Create opportunities for growth, learning, and transformation for the people and communities we work with.',
                    'link_url'    => '',
                    'link_label'  => '',
                ],
            ],
            [
                'sort_order' => 2,
                'es' => [
                    'title'       => 'Visión',
                    'description' => 'Ser un referente en el desarrollo comunitario, reconocido por el impacto real y medible que generamos en cada contexto donde actuamos.',
                    'link_url'    => '',
                    'link_label'  => '',
                ],
                'en' => [
                    'title'       => 'Vision',
                    'description' => 'To be a benchmark in community development, recognized for the real and measurable impact we generate in every context we act in.',
                    'link_url'    => '',
                    'link_label'  => '',
                ],
            ],
            [
                'sort_order' => 3,
                'es' => [
                    'title'       => 'Valores',
                    'description' => 'Colaboración, integridad, innovación y compromiso con las personas. Son los principios que guían cada decisión que tomamos.',
                    'link_url'    => '',
                    'link_label'  => '',
                ],
                'en' => [
                    'title'       => 'Values',
                    'description' => 'Collaboration, integrity, innovation, and commitment to people. These are the principles that guide every decision we make.',
                    'link_url'    => '',
                    'link_label'  => '',
                ],
            ],
        ];
        $this->seedChildBlocks($aboutPageId, $featuresGridId, 'card_item', $featureCards, $blockIds, $langIds);

        // ── 5. metrics_grid (primary) + 3 metric_item children ────────────────
        $statsSectionId = $this->upsertBlock(
            $aboutPageId,
            $blockIds,
            'metrics_grid',
            5,
            ['variant' => 'primary', 'css_class' => ''],
            ['es' => [], 'en' => []],
            $langIds
        );

        $statItems = [
            [
                'sort_order' => 1,
                'es' => ['number' => '10',  'suffix' => '+', 'label' => 'Años de trayectoria',    'description' => 'Programas activos desde nuestros primeros proyectos.', 'source_label' => 'Reporte interno', 'icon' => 'calendar'],
                'en' => ['number' => '10',  'suffix' => '+', 'label' => 'Years of experience',    'description' => 'Active programs since our first projects.', 'source_label' => 'Internal report', 'icon' => 'calendar'],
            ],
            [
                'sort_order' => 2,
                'es' => ['number' => '500', 'suffix' => '+', 'label' => 'Personas alcanzadas',    'description' => 'Participantes directos e indirectos.', 'source_label' => 'Base comunitaria', 'icon' => 'users'],
                'en' => ['number' => '500', 'suffix' => '+', 'label' => 'People reached',         'description' => 'Direct and indirect participants.', 'source_label' => 'Community database', 'icon' => 'users'],
            ],
            [
                'sort_order' => 3,
                'es' => ['number' => '25',  'suffix' => '+', 'label' => 'Aliados estratégicos',   'description' => 'Organizaciones que colaboran en iniciativas.', 'source_label' => 'Alianzas vigentes', 'icon' => 'handshake'],
                'en' => ['number' => '25',  'suffix' => '+', 'label' => 'Strategic partners',     'description' => 'Organizations collaborating on initiatives.', 'source_label' => 'Active partnerships', 'icon' => 'handshake'],
            ],
        ];
        $this->seedChildBlocks($aboutPageId, $statsSectionId, 'metric_item', $statItems, $blockIds, $langIds);

        // ── 6. cards_slider + 2 slide_card children ─────────────
        $testimonialsSliderId = $this->upsertBlock(
            $aboutPageId,
            $blockIds,
            'cards_slider',
            6,
            ['layout' => 'slider', 'autoplay' => true, 'interval' => 5000, 'visible_count' => '1', 'card_variant' => 'testimonial', 'css_class' => ''],
            ['es' => [], 'en' => []],
            $langIds
        );

        $testimonialCards = [
            [
                'sort_order' => 1,
                'es' => [
                    'eyebrow' => 'Testimonio',
                    'body'    => 'Participar en los programas de esta organización cambió mi perspectiva por completo. Aprendí herramientas concretas y construí una red de personas increíbles.',
                    'meta_title' => 'María González',
                    'meta_description' => 'Participante del programa 2022',
                    'rating' => '5',
                ],
                'en' => [
                    'eyebrow' => 'Testimonial',
                    'body'    => 'Participating in this organization\'s programs completely changed my perspective. I learned concrete tools and built a network of incredible people.',
                    'meta_title' => 'María González',
                    'meta_description' => 'Program participant 2022',
                    'rating' => '5',
                ],
            ],
            [
                'sort_order' => 2,
                'es' => [
                    'eyebrow' => 'Testimonio',
                    'body'    => 'Colaborar con este equipo fue una experiencia transformadora. Su compromiso con la comunidad es genuino y se nota en cada detalle del trabajo que hacen.',
                    'meta_title' => 'Carlos Rodríguez',
                    'meta_description' => 'Aliado institucional',
                    'rating' => '5',
                ],
                'en' => [
                    'eyebrow' => 'Testimonial',
                    'body'    => 'Collaborating with this team was a transformative experience. Their commitment to the community is genuine and shows in every detail of the work they do.',
                    'meta_title' => 'Carlos Rodríguez',
                    'meta_description' => 'Institutional partner',
                    'rating' => '5',
                ],
            ],
        ];
        $this->seedChildBlocks($aboutPageId, $testimonialsSliderId, 'slide_card', $testimonialCards, $blockIds, $langIds);

        // ── 7. asset_showcase (aliados) + 3 asset_item children ────────────────
        $logoShowcaseId = $this->upsertBlock(
            $aboutPageId,
            $blockIds,
            'asset_showcase',
            7,
            ['layout' => 'marquee', 'speed' => 'normal', 'grayscale' => true, 'css_class' => ''],
            ['es' => [], 'en' => []],
            $langIds
        );

        $logoItems = [
            [
                'sort_order' => 1,
                'es' => ['name' => 'Aliado A', 'link_url' => ''],
                'en' => ['name' => 'Partner A', 'link_url' => ''],
            ],
            [
                'sort_order' => 2,
                'es' => ['name' => 'Aliado B', 'link_url' => ''],
                'en' => ['name' => 'Partner B', 'link_url' => ''],
            ],
            [
                'sort_order' => 3,
                'es' => ['name' => 'Aliado C', 'link_url' => ''],
                'en' => ['name' => 'Partner C', 'link_url' => ''],
            ],
        ];
        $this->seedChildBlocks($aboutPageId, $logoShowcaseId, 'asset_item', $logoItems, $blockIds, $langIds);

        // ── 8. accordion + 3 accordion_item children ────────────────────────────
        $faqAccordionId = $this->upsertBlock(
            $aboutPageId,
            $blockIds,
            'accordion',
            8,
            ['css_class' => ''],
            ['es' => [], 'en' => []],
            $langIds
        );

        $faqItems = [
            [
                'sort_order' => 1,
                'is_open'    => true,
                'es' => [
                    'title'    => '¿Cuál es la misión de la organización?',
                    'content'  => '<p>Nuestra misión es crear oportunidades de crecimiento y transformación para las personas y comunidades con las que trabajamos, a través de programas, proyectos e iniciativas con impacto medible.</p>',
                ],
                'en' => [
                    'title'    => 'What is the organization\'s mission?',
                    'content'  => '<p>Our mission is to create opportunities for growth and transformation for the people and communities we work with, through programs, projects, and initiatives with measurable impact.</p>',
                ],
            ],
            [
                'sort_order' => 2,
                'is_open'    => false,
                'es' => [
                    'title'    => '¿Quiénes pueden participar en sus programas?',
                    'content'  => '<p>Nuestros programas están abiertos a personas, organizaciones e instituciones que compartan nuestros valores y quieran sumarse a un proceso de aprendizaje y desarrollo. Contáctanos para conocer los requisitos específicos de cada iniciativa.</p>',
                ],
                'en' => [
                    'title'    => 'Who can participate in your programs?',
                    'content'  => '<p>Our programs are open to individuals, organizations, and institutions that share our values and want to join a learning and development process. Contact us to learn about the specific requirements for each initiative.</p>',
                ],
            ],
            [
                'sort_order' => 3,
                'is_open'    => false,
                'es' => [
                    'title'    => '¿Cómo puedo colaborar con la organización?',
                    'content'  => '<p>Hay muchas formas de colaborar: como voluntario, como aliado institucional, como patrocinador o simplemente difundiendo nuestro trabajo. Escríbenos a través del formulario de contacto y te contamos cómo puedes sumarte.</p>',
                ],
                'en' => [
                    'title'    => 'How can I collaborate with the organization?',
                    'content'  => '<p>There are many ways to collaborate: as a volunteer, as an institutional partner, as a sponsor, or simply by spreading the word about our work. Write to us through the contact form and we will tell you how you can get involved.</p>',
                ],
            ],
        ];

        foreach ($faqItems as $faqItem) {
            $blockId = $blockIds['accordion_item'] ?? null;
            if ($blockId === null) {
                continue;
            }

            $existing = $this->db->table('cms_block_instances')
                ->where('block_id', $blockId)
                ->where('parent_instance_id', $faqAccordionId)
                ->where('sort_order', (int) $faqItem['sort_order'])
                ->get()
                ->getRowArray();

            $payload = [
                'block_id'           => $blockId,
                'owner_type'         => 'page',
                'owner_id'           => $aboutPageId,
                'parent_instance_id' => $faqAccordionId,
                'sort_order'         => (int) $faqItem['sort_order'],
                'column_index'       => null,
                'is_active'          => 1,
                'block_config'       => json_encode(['is_open' => $faqItem['is_open']], JSON_UNESCAPED_UNICODE),
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

            foreach (['es', 'en'] as $lang) {
                $langId = $langIds[$lang] ?? null;
                if ($langId === null || ! isset($faqItem[$lang])) {
                    continue;
                }
                $this->upsertTranslation($instanceId, $langId, $faqItem[$lang]);
            }
        }

        // ── 9. video_player ───────────────────────────────────────────────────
        $this->upsertBlock(
            $aboutPageId,
            $blockIds,
            'video_player',
            9,
            ['autoplay' => false, 'mute' => false, 'loop' => false, 'aspect_ratio' => '16/9', 'css_class' => ''],
            [
                'es' => ['heading' => 'Conoce nuestra misión'],
                'en' => ['heading' => 'Learn about our mission'],
            ],
            $langIds
        );
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function upsertPage(): int
    {
        $existing = $this->db->table('cms_pages')
            ->where('page_type', 'about')
            ->where('deleted_at IS NULL', null, false)
            ->get()
            ->getRowArray();

        $payload = [
            'page_type'          => 'about',
            'status'             => 'published',
            'published_at'       => date('Y-m-d H:i:s'),
            'scheduled_at'       => null,
            'sort_order'         => 30,
            'sitemap_priority'   => '0.7',
            'sitemap_changefreq' => 'monthly',
            'is_in_sitemap'      => 1,
        ];

        if ($existing === null) {
            $this->db->table('cms_pages')->insert($payload);
            return (int) $this->db->insertID();
        }

        $this->db->table('cms_pages')->where('id', (int) $existing['id'])->update($payload);
        return (int) $existing['id'];
    }

    /** @param array<string, mixed> $translationData */
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

        $payload = array_merge(
            ['page_id' => $pageId, 'language_id' => $languageId],
            $translationData,
            ['updated_at' => date('Y-m-d H:i:s')]
        );

        if ($existing === null) {
            $this->db->table('cms_page_translations')->insert(array_merge($payload, ['created_at' => date('Y-m-d H:i:s')]));
            return;
        }

        unset($payload['page_id'], $payload['language_id'], $payload['created_at']);
        $this->db->table('cms_page_translations')->where('id', (int) $existing['id'])->update($payload);
    }

    /**
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
            echo "SiteAboutPageSeeder: block type '{$blockKey}' not found — skipped.\n";
            return 0;
        }

        $query = $this->db->table('cms_block_instances')
            ->where('block_id', $blockId)
            ->where('owner_type', 'page')
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
            'owner_type'         => 'page',
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
            $existing = $this->db->table('cms_block_instances')
                ->where('block_id', $blockId)
                ->where('parent_instance_id', $parentInstanceId)
                ->where('sort_order', (int) $item['sort_order'])
                ->get()
                ->getRowArray();

            $payload = [
                'block_id'           => $blockId,
                'owner_type'         => 'page',
                'owner_id'           => $pageId,
                'parent_instance_id' => $parentInstanceId,
                'sort_order'         => (int) $item['sort_order'],
                'column_index'       => null,
                'is_active'          => 1,
                'block_config'       => json_encode([], JSON_UNESCAPED_UNICODE),
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
