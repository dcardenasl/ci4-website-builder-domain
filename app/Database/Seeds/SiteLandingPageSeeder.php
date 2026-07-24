<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use App\Database\Seeds\Concerns\IdempotentSeederSupport;
use CodeIgniter\Database\Seeder;

/**
 * Creates the Landing Page with the following blocks:
 *   page_header,
 *   anchor_nav,
 *   features_grid,
 *   process_steps,
 *   pricing_grid + pricing_plan children,
 *   video_gallery,
 *   faq_accordion,
 *   cta.
 */
class SiteLandingPageSeeder extends Seeder
{
    use IdempotentSeederSupport;

    public function run(): void
    {
        $langIds = $this->langIds(['es', 'en']);
        if (! isset($langIds['es'], $langIds['en'])) {
            echo "SiteLandingPageSeeder: missing languages. Seed CmsLanguageSeeder first.\n";
            return;
        }

        $landingPageId = $this->upsertPage();
        $this->upsertPageTranslation($landingPageId, $langIds['es'], [
            'slug'             => 'landing',
            'title'            => 'Landing Page',
            'excerpt'          => 'Página de presentación interactiva de nuestros servicios.',
            'meta_title'       => 'Página de Destino | Mi Sitio',
            'meta_description' => 'Un escaparate interactivo de nuestras principales funcionalidades, planes y preguntas frecuentes.',
            'canonical_url'    => null,
            'robots'           => 'index, follow',
            'schema_data'      => null,
        ]);
        $this->upsertPageTranslation($landingPageId, $langIds['en'], [
            'slug'             => 'landing',
            'title' => 'Landing Page',
            'excerpt'          => 'Interactive showcase page of our services.',
            'meta_title'       => 'Landing Page | My Site',
            'meta_description' => 'An interactive showcase of our core features, plans, and frequently asked questions.',
            'canonical_url'    => null,
            'robots'           => 'index, follow',
            'schema_data'      => null,
        ]);

        $this->resetPageBlocks($landingPageId);

        $blockIds = $this->blockIds([
            'page_header',
            'anchor_nav',
            'features_grid',
            'process_steps',
            'pricing_grid',
            'pricing_plan',
            'video_gallery',
            'faq_accordion',
            'cta',
        ]);

        // ── 1. page_header ────────────────────────────────────────────────────
        $this->upsertBlock(
            $landingPageId,
            $blockIds,
            'page_header',
            1,
            ['bg_color' => 'bg-slate-50', 'css_class' => ''],
            [
                'es' => [
                    'heading'          => 'Página de Destino',
                    'subheading'       => 'Una landing page interactiva con menú de anclas, precios, FAQs y videos.',
                    'breadcrumb_label' => 'Inicio',
                    'breadcrumb_url'   => '/',
                ],
                'en' => [
                    'heading'          => 'Landing Page',
                    'subheading'       => 'An interactive landing page featuring anchor navigation, pricing, FAQs, and videos.',
                    'breadcrumb_label' => 'Home',
                    'breadcrumb_url'   => '/',
                ],
            ],
            $langIds
        );

        // ── 2. anchor_nav ────────────────────────────────────────────────────
        $this->upsertBlock(
            $landingPageId,
            $blockIds,
            'anchor_nav',
            2,
            ['css_class' => ''],
            [
                'es' => [
                    'anchors' => [
                        ['label' => 'Características', 'anchor_id' => '#features'],
                        ['label' => 'Proceso', 'anchor_id' => '#process'],
                        ['label' => 'Precios', 'anchor_id' => '#pricing'],
                        ['label' => 'Videos', 'anchor_id' => '#timeline-item-videos'],
                        ['label' => 'Preguntas Frecuentes', 'anchor_id' => '#faq'],
                    ],
                ],
                'en' => [
                    'anchors' => [
                        ['label' => 'Features', 'anchor_id' => '#features'],
                        ['label' => 'Process', 'anchor_id' => '#process'],
                        ['label' => 'Pricing', 'anchor_id' => '#pricing'],
                        ['label' => 'Videos', 'anchor_id' => '#timeline-item-videos'],
                        ['label' => 'FAQ', 'anchor_id' => '#faq'],
                    ],
                ],
            ],
            $langIds
        );

        // ── 3. features_grid ─────────────────────────────────────────────────
        $this->upsertBlock(
            $landingPageId,
            $blockIds,
            'features_grid',
            3,
            ['columns' => '3', 'css_class' => 'my-12'],
            [
                'es' => [
                    'title'       => '¿Por qué elegirnos?',
                    'description' => 'Ofrecemos soluciones robustas e innovadoras diseñadas para potenciar tu productividad y escalabilidad.',
                    'features'    => [
                        [
                            'icon_name'   => 'zap',
                            'title'       => 'Rendimiento Extremo',
                            'description' => 'Desarrollado sobre CodeIgniter 4 para garantizar tiempos de respuesta por debajo de los 50ms.',
                        ],
                        [
                            'icon_name'   => 'shield',
                            'title'       => 'Seguridad Integrada',
                            'description' => 'Protección nativa contra CSRF, XSS e inyecciones SQL en todas las capas del sistema.',
                        ],
                        [
                            'icon_name'   => 'layout',
                            'title'       => 'Diseño Adaptativo',
                            'description' => 'Componentes maquetados con Tailwind CSS que se visualizan perfectos en móviles y desktops.',
                        ],
                    ],
                ],
                'en' => [
                    'title'       => 'Why choose us?',
                    'description' => 'We offer robust and innovative solutions designed to boost your productivity and scalability.',
                    'features'    => [
                        [
                            'icon_name'   => 'zap',
                            'title'       => 'Extreme Performance',
                            'description' => 'Built on CodeIgniter 4 to guarantee response times below 50ms.',
                        ],
                        [
                            'icon_name'   => 'shield',
                            'title'       => 'Built-in Security',
                            'description' => 'Native protection against CSRF, XSS, and SQL injection across all layers.',
                        ],
                        [
                            'icon_name'   => 'layout',
                            'title'       => 'Responsive Layouts',
                            'description' => 'Components designed with Tailwind CSS that look pixel-perfect on mobile and desktop.',
                        ],
                    ],
                ],
            ],
            $langIds
        );

        // ── 4. process_steps ─────────────────────────────────────────────────
        $this->upsertBlock(
            $landingPageId,
            $blockIds,
            'process_steps',
            4,
            ['css_class' => 'my-12'],
            [
                'es' => [
                    'title'       => 'Cómo Comenzar',
                    'description' => 'Tres sencillos pasos para activar tu sitio web y empezar a publicar contenidos estructurados.',
                    'steps'       => [
                        [
                            'step_number' => '1',
                            'title'       => 'Registrar Cuenta',
                            'description' => 'Crea tu usuario administrador e inicializa los esquemas por defecto.',
                        ],
                        [
                            'step_number' => '2',
                            'title'       => 'Configurar Bloques',
                            'description' => 'Arrastra y personaliza los bloques de contenido visuales en tus páginas.',
                        ],
                        [
                            'step_number' => '3',
                            'title'       => 'Publicar Sitio',
                            'description' => 'Presiona publicar para sincronizar tus contenidos en producción de inmediato.',
                        ],
                    ],
                ],
                'en' => [
                    'title'       => 'How to Get Started',
                    'description' => 'Three simple steps to activate your website and start publishing structured content.',
                    'steps'       => [
                        [
                            'step_number' => '1',
                            'title'       => 'Register Account',
                            'description' => 'Create your administrator user and initialize the default database schemes.',
                        ],
                        [
                            'step_number' => '2',
                            'title'       => 'Configure Blocks',
                            'description' => 'Drag, drop, and customize visual content blocks on your pages.',
                        ],
                        [
                            'step_number' => '3',
                            'title'       => 'Publish Site',
                            'description' => 'Hit publish to synchronize your contents to production immediately.',
                        ],
                    ],
                ],
            ],
            $langIds
        );

        // ── 5. pricing_grid ──────────────────────────────────────────────────
        $pricingGridId = $this->upsertBlock(
            $landingPageId,
            $blockIds,
            'pricing_grid',
            5,
            ['css_class' => 'my-12 bg-slate-50/50 rounded-3xl p-8 border border-slate-200/50'],
            [
                'es' => [
                    'title'       => 'Planes y Precios',
                    'description' => 'Elige el plan ideal para tu organización. Todos los planes incluyen actualizaciones de seguridad periódicas.',
                ],
                'en' => [
                    'title'       => 'Plans & Pricing',
                    'description' => 'Choose the ideal plan for your organization. All plans include regular security updates.',
                ],
            ],
            $langIds
        );

        $pricingPlans = [
            [
                'sort_order' => 1,
                'featured'   => false,
                'es' => [
                    'name'        => 'Básico',
                    'price'       => '$19',
                    'period'      => '/ mes',
                    'description' => 'Ideal para sitios personales o startups en etapa temprana.',
                    'features'    => '<ul><li>Hasta 5 páginas</li><li>3 usuarios editores</li><li>Soporte vía email</li><li>SSL gratuito</li></ul>',
                    'cta_label'   => 'Iniciar plan Básico',
                    'cta_url'     => '/registro?plan=basic',
                ],
                'en' => [
                    'name'        => 'Basic',
                    'price'       => '$19',
                    'period'      => '/ mo',
                    'description' => 'Perfect for personal sites or early-stage startups.',
                    'features'    => '<ul><li>Up to 5 pages</li><li>3 editor users</li><li>Email support</li><li>Free SSL</li></ul>',
                    'cta_label'   => 'Start Basic plan',
                    'cta_url'     => '/register?plan=basic',
                ],
            ],
            [
                'sort_order' => 2,
                'featured'   => true,
                'es' => [
                    'name'        => 'Profesional',
                    'price'       => '$49',
                    'period'      => '/ mes',
                    'description' => 'Para organizaciones medianas con múltiples colaboradores.',
                    'features'    => '<ul><li>Páginas ilimitadas</li><li>10 usuarios editores</li><li>Soporte 24/7 prioritario</li><li>CDN y optimización de imágenes</li><li>Traducciones multi-idioma</li></ul>',
                    'cta_label'   => 'Iniciar plan Profesional',
                    'cta_url'     => '/registro?plan=pro',
                ],
                'en' => [
                    'name'        => 'Professional',
                    'price'       => '$49',
                    'period'      => '/ mo',
                    'description' => 'For medium-sized organizations with multiple editors.',
                    'features'    => '<ul><li>Unlimited pages</li><li>10 editor users</li><li>24/7 priority support</li><li>CDN & image optimization</li><li>Multi-language translation</li></ul>',
                    'cta_label'   => 'Start Professional plan',
                    'cta_url'     => '/register?plan=pro',
                ],
            ],
        ];

        foreach ($pricingPlans as $plan) {
            $planBlockId = $blockIds['pricing_plan'] ?? null;
            if ($planBlockId === null) {
                continue;
            }

            $pInstanceId = $this->upsertRecord('cms_block_instances', [
                'block_id'           => $planBlockId,
                'owner_type'         => 'page',
                'owner_id'           => $landingPageId,
                'parent_instance_id' => $pricingGridId,
                'sort_order'         => $plan['sort_order'],
            ], [
                'column_index' => null,
                'is_active'    => 1,
                'block_config'  => json_encode(['featured' => $plan['featured']], JSON_UNESCAPED_UNICODE),
            ]);

            if ($pInstanceId === null) {
                continue;
            }

            foreach (['es', 'en'] as $lang) {
                $langId = $langIds[$lang] ?? null;
                if ($langId === null || ! isset($plan[$lang])) {
                    continue;
                }
                $this->upsertTranslation($pInstanceId, $langId, $plan[$lang]);
            }
        }

        // ── 6. video_gallery ─────────────────────────────────────────────────
        $this->upsertBlock(
            $landingPageId,
            $blockIds,
            'video_gallery',
            6,
            ['columns' => '3', 'css_class' => 'my-12 scroll-mt-16'],
            [
                'es' => [
                    'title'    => 'Colección de Videos del Starter',
                    'subtitle' => 'Una muestra de videos de YouTube y Vimeo integrados en una grilla interactiva.',
                    'videos'   => [
                        [
                            'video_url'   => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                            'title'       => 'Rick Astley - Never Gonna Give You Up',
                            'description' => 'Un clásico de la cultura de internet para pruebas de reproducción.',
                            'poster'      => $this->mediaReference('https://images.unsplash.com/photo-1611162617213-7d7a39e9b1d7?w=600&auto=format&fit=crop&q=60'),
                        ],
                        [
                            'video_url'   => 'https://vimeo.com/76979871',
                            'title'       => 'The Mountain (Timelapse)',
                            'description' => 'Un espectacular timelapse de montañas de Vimeo.',
                            'poster'      => $this->mediaReference('https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?w=600&auto=format&fit=crop&q=60'),
                        ],
                        [
                            'video_url'   => 'https://www.youtube.com/watch?v=ScMzIvxBSi4',
                            'title'       => 'Lo-Fi Beats to Study/Relax',
                            'description' => 'Música ambiental y relajante en vivo.',
                            'poster'      => $this->mediaReference('https://images.unsplash.com/photo-1518609878373-06d740f60d8b?w=600&auto=format&fit=crop&q=60'),
                        ],
                    ],
                ],
                'en' => [
                    'title'    => 'Starter Video Collection',
                    'subtitle' => 'A selection of YouTube and Vimeo videos integrated into a responsive grid.',
                    'videos'   => [
                        [
                            'video_url'   => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                            'title'       => 'Rick Astley - Never Gonna Give You Up',
                            'description' => 'A classic internet culture video for playback testing.',
                            'poster'      => $this->mediaReference('https://images.unsplash.com/photo-1611162617213-7d7a39e9b1d7?w=600&auto=format&fit=crop&q=60'),
                        ],
                        [
                            'video_url'   => 'https://vimeo.com/76979871',
                            'title'       => 'The Mountain (Timelapse)',
                            'description' => 'A stunning mountain timelapse from Vimeo.',
                            'poster'      => $this->mediaReference('https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?w=600&auto=format&fit=crop&q=60'),
                        ],
                        [
                            'video_url'   => 'https://www.youtube.com/watch?v=ScMzIvxBSi4',
                            'title'       => 'Lo-Fi Beats to Study/Relax',
                            'description' => 'Ambient chill music stream.',
                            'poster'      => $this->mediaReference('https://images.unsplash.com/photo-1518609878373-06d740f60d8b?w=600&auto=format&fit=crop&q=60'),
                        ],
                    ],
                ],
            ],
            $langIds
        );

        // ── 7. faq_accordion ─────────────────────────────────────────────────
        $this->upsertBlock(
            $landingPageId,
            $blockIds,
            'faq_accordion',
            7,
            ['css_class' => 'my-12'],
            [
                'es' => [
                    'title'       => 'Preguntas Frecuentes',
                    'description' => 'Resolvemos tus dudas principales acerca del CMS y la inicialización de la base de datos.',
                    'faqs'        => [
                        [
                            'question' => '¿Qué versión de PHP requiere este proyecto?',
                            'answer'   => '<p>El proyecto requiere PHP 8.1 o superior, junto con las extensiones curl, intl, mbstring y mysql habilitadas en el servidor.</p>',
                        ],
                        [
                            'question' => '¿Cómo puedo compilar los assets del sitio web?',
                            'answer'   => '<p>El starter utiliza Tailwind CSS para compilar las hojas de estilo. Puedes ejecutar <code>npm run dev</code> o <code>npm run build</code> en la raíz del tema.</p>',
                        ],
                        [
                            'question' => '¿Tiene soporte para bases de datos SQLite?',
                            'answer'   => '<p>Actualmente el proyecto está optimizado y configurado para ejecutarse sobre MySQL / MariaDB por cuestiones de estabilidad.</p>',
                        ],
                    ],
                ],
                'en' => [
                    'title'       => 'Frequently Asked Questions',
                    'description' => 'Answering your main questions about the CMS and database initialization.',
                    'faqs'        => [
                        [
                            'question' => 'What PHP version does this project require?',
                            'answer'   => '<p>The project requires PHP 8.1 or higher, with curl, intl, mbstring, and mysql extensions enabled on your server.</p>',
                        ],
                        [
                            'question' => 'How can I compile the website assets?',
                            'answer'   => '<p>The starter package uses Tailwind CSS for stylesheet compilation. You can run <code>npm run dev</code> or <code>npm run build</code> inside the theme root.</p>',
                        ],
                        [
                            'question' => 'Does it support SQLite databases?',
                            'answer'   => '<p>Currently, the project is optimized and configured to run on MySQL / MariaDB for production stability.</p>',
                        ],
                    ],
                ],
            ],
            $langIds
        );

        // ── 8. cta ───────────────────────────────────────────────────────────
        $this->upsertBlock(
            $landingPageId,
            $blockIds,
            'cta',
            8,
            ['variant' => 'blue', 'css_class' => ''],
            [
                'es' => [
                    'heading' => '¿Listo para lanzar tu proyecto?',
                    'text'    => 'Descarga el starter kit y comienza a construir interfaces premium hoy mismo.',
                    'label'   => 'Comenzar Ahora',
                    'url'     => '/es/contacto',
                ],
                'en' => [
                    'heading' => 'Ready to launch your project?',
                    'text'    => 'Download the starter kit and start building premium web layouts today.',
                    'label'   => 'Get Started Now',
                    'url'     => '/en/contact',
                ],
            ],
            $langIds
        );
    }

    private function upsertPage(): int
    {
        $existing = $this->db->table('cms_pages')
            ->select('cms_pages.id')
            ->join('cms_page_translations', 'cms_page_translations.page_id = cms_pages.id')
            ->where('cms_page_translations.slug', 'landing')
            ->where('cms_pages.deleted_at IS NULL', null, false)
            ->get()
            ->getRowArray();

        if ($existing !== null) {
            $pageId = (int) $existing['id'];
            $updatePayload = [
                'page_type'          => 'generic',
                'status'             => 'published',
                'published_at'       => date('Y-m-d H:i:s'),
                'scheduled_at'       => null,
                'sort_order'         => 38,
                'sitemap_priority'   => '0.8',
                'sitemap_changefreq' => 'weekly',
                'is_in_sitemap'      => 1,
                'deleted_at'         => null,
            ];
            if ($this->db->fieldExists('updated_at', 'cms_pages')) {
                $updatePayload['updated_at'] = date('Y-m-d H:i:s');
            }

            $this->db->table('cms_pages')
                ->where('id', $pageId)
                ->update($updatePayload);
        } else {
            $pageId = $this->createRecord('cms_pages', [
                'page_type'          => 'generic',
                'status'             => 'published',
                'published_at'       => date('Y-m-d H:i:s'),
                'scheduled_at'       => null,
                'sort_order'         => 38,
                'sitemap_priority'   => '0.8',
                'sitemap_changefreq' => 'weekly',
                'is_in_sitemap'      => 1,
                'deleted_at'         => null,
            ]);
        }

        if ($pageId === null) {
            throw new \RuntimeException('SiteLandingPageSeeder: unable to seed landing page.');
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

    /** @param string[] $keys  @return array<string, int> */
    private function blockIds(array $keys): array
    {
        if ($keys === []) {
            return [];
        }

        $rows = $this->db->table('cms_content_blocks')
            ->select('id, block_key')
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
            echo "SiteLandingPageSeeder: block type '{$blockKey}' not found — skipped.\n";
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
