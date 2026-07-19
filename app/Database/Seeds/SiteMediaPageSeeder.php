<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use App\Database\Seeds\Concerns\IdempotentSeederSupport;
use CodeIgniter\Database\Seeder;

/**
 * Creates the Media / Multimedia page that showcases gallery presentation
 * modes, video playback, and a lightweight editorial structure for mixed
 * media content.
 *
 * The goal is to keep every public presentation mode represented in a real,
 * navigable page so the starter can be audited visually without hidden routes
 * or compatibility fallbacks.
 */
class SiteMediaPageSeeder extends Seeder
{
    use IdempotentSeederSupport;

    public function run(): void
    {
        $langIds = $this->langIds(['es', 'en']);
        if (! isset($langIds['es'], $langIds['en'])) {
            echo "SiteMediaPageSeeder: missing languages. Seed CmsLanguageSeeder first.\n";
            return;
        }

        $pageId = $this->upsertPage();
        $this->upsertPageTranslation($pageId, $langIds['es'], [
            'slug'             => 'multimedia',
            'title'            => 'Multimedia',
            'excerpt'          => 'Galerías, video y recursos visuales representados en una página pública real.',
            'meta_title'       => 'Multimedia | Mi Sitio',
            'meta_description' => 'Explora las distintas formas de presentar galerías, videos y recursos visuales.',
            'canonical_url'    => null,
            'robots'           => 'index, follow',
            'schema_data'      => null,
        ]);
        $this->upsertPageTranslation($pageId, $langIds['en'], [
            'slug'             => 'media',
            'title'            => 'Media',
            'excerpt'          => 'Galleries, video, and visual resources represented on a real public page.',
            'meta_title'       => 'Media | My Site',
            'meta_description' => 'Explore different ways to present galleries, videos, and visual resources.',
            'canonical_url'    => null,
            'robots'           => 'index, follow',
            'schema_data'      => null,
        ]);

        $this->resetPageBlocks($pageId);

        $blockIds = $this->blockIds([
            'page_header',
            'rich_text',
            'gallery',
            'gallery_item',
            'video_player',
            'document_gallery',
            'pdf_viewer',
            'external_links',
            'alert',
            'cta',
        ]);

        $this->upsertBlock(
            $pageId,
            $blockIds,
            'page_header',
            1,
            ['bg_color' => 'bg-gray-100', 'css_class' => ''],
            [
                'es' => [
                    'heading'          => 'Multimedia',
                    'subheading'       => 'Una página pública para auditar galerías, video y recursos visuales.',
                    'breadcrumb_label' => 'Inicio',
                    'breadcrumb_url'   => '/',
                ],
                'en' => [
                    'heading'          => 'Media',
                    'subheading'       => 'A public page to audit galleries, video, and visual resources.',
                    'breadcrumb_label' => 'Home',
                    'breadcrumb_url'   => '/',
                ],
            ],
            $langIds
        );

        $this->upsertBlock(
            $pageId,
            $blockIds,
            'rich_text',
            2,
            ['css_class' => ''],
            [
                'es' => [
                    'content' => '<p>Esta página muestra de forma explícita los modos de presentación del bloque de galería y el bloque de video. Así el starter no depende de conocimientos implícitos ni de rutas escondidas para demostrar sus capacidades multimedia.</p>',
                ],
                'en' => [
                    'content' => '<p>This page explicitly shows the gallery presentation modes and the video block. That keeps the starter from depending on implicit knowledge or hidden routes to demonstrate its media capabilities.</p>',
                ],
            ],
            $langIds
        );

        $this->upsertBlock(
            $pageId,
            $blockIds,
            'alert',
            3,
            ['type' => 'info', 'dismissible' => false, 'css_class' => 'my-8'],
            [
                'es' => [
                    'title'   => 'Cobertura multimedia',
                    'message' => '<p>Si un modo visual existe en el CMS, debería poder verse aquí sin intervención manual. La página está pensada para detectar regresiones de UI y de contenido con una sola visita.</p>',
                ],
                'en' => [
                    'title'   => 'Media coverage',
                    'message' => '<p>If a visual mode exists in the CMS, it should be visible here without manual intervention. This page is designed to catch UI and content regressions in a single visit.</p>',
                ],
            ],
            $langIds
        );

        $gridGalleryId = $this->upsertBlock(
            $pageId,
            $blockIds,
            'gallery',
            4,
            ['presentation_mode' => 'grid', 'columns' => '4', 'gap' => 'medium', 'css_class' => 'my-10'],
            ['es' => [], 'en' => []],
            $langIds
        );
        $this->seedChildBlocks($pageId, $gridGalleryId, 'gallery_item', [
            [
                'sort_order' => 1,
                'config'     => ['image' => $this->mediaReference('https://picsum.photos/id/1040/1200/900')],
                'es' => [
                    'alt'       => 'Galería en cuadrícula 1',
                    'caption'   => 'Vista en cuadrícula para series cortas y descubrimiento rápido.',
                ],
                'en' => [
                    'alt'       => 'Grid gallery 1',
                    'caption'   => 'Grid view for short series and quick discovery.',
                ],
            ],
            [
                'sort_order' => 2,
                'config'     => ['image' => $this->mediaReference('https://picsum.photos/id/1041/1200/900')],
                'es' => [
                    'alt'       => 'Galería en cuadrícula 2',
                    'caption'   => 'Cada tarjeta mantiene una jerarquía clara y respirable.',
                ],
                'en' => [
                    'alt'       => 'Grid gallery 2',
                    'caption'   => 'Each card keeps a clear and breathable hierarchy.',
                ],
            ],
            [
                'sort_order' => 3,
                'config'     => ['image' => $this->mediaReference('https://picsum.photos/id/1042/1200/900')],
                'es' => [
                    'alt'       => 'Galería en cuadrícula 3',
                    'caption'   => 'Ideal para grillas editoriales o colecciones de referencia.',
                ],
                'en' => [
                    'alt'       => 'Grid gallery 3',
                    'caption'   => 'Ideal for editorial grids or reference collections.',
                ],
            ],
            [
                'sort_order' => 4,
                'config'     => ['image' => $this->mediaReference('https://picsum.photos/id/1043/1200/900')],
                'es' => [
                    'alt'       => 'Galería en cuadrícula 4',
                    'caption'   => 'La relación de aspecto se mantiene consistente.',
                ],
                'en' => [
                    'alt'       => 'Grid gallery 4',
                    'caption'   => 'Aspect ratio stays consistent.',
                ],
            ],
        ], $blockIds, $langIds);

        $this->upsertBlock(
            $pageId,
            $blockIds,
            'rich_text',
            5,
            ['css_class' => ''],
            [
                'es' => [
                    'content' => '<h2>Previsualización integrada</h2><p>Este modo permite revisar una imagen destacada sin abrir un modal. Es útil cuando quieres mantener la lectura dentro del flujo de la página y darle prioridad a una pieza visual a la vez.</p>',
                ],
                'en' => [
                    'content' => '<h2>Integrated preview</h2><p>This mode lets you inspect a featured image without opening a modal. It works well when you want to keep the reading flow on the page and focus on one visual at a time.</p>',
                ],
            ],
            $langIds
        );

        $inlineGalleryId = $this->upsertBlock(
            $pageId,
            $blockIds,
            'gallery',
            6,
            ['presentation_mode' => 'inline_preview', 'columns' => '3', 'gap' => 'large', 'css_class' => 'my-10'],
            ['es' => [], 'en' => []],
            $langIds
        );
        $this->seedChildBlocks($pageId, $inlineGalleryId, 'gallery_item', [
            [
                'sort_order' => 1,
                'config'     => ['image' => $this->mediaReference('https://picsum.photos/id/1050/1200/900')],
                'es' => [
                    'alt'       => 'Vista inline 1',
                    'caption'   => 'La previsualización cambia sin abandonar la página.',
                ],
                'en' => [
                    'alt'       => 'Inline preview 1',
                    'caption'   => 'The preview updates without leaving the page.',
                ],
            ],
            [
                'sort_order' => 2,
                'config'     => ['image' => $this->mediaReference('https://picsum.photos/id/1051/1200/900')],
                'es' => [
                    'alt'       => 'Vista inline 2',
                    'caption'   => 'Sirve para colecciones seleccionadas o pequeñas galerías.',
                ],
                'en' => [
                    'alt'       => 'Inline preview 2',
                    'caption'   => 'Useful for curated collections or small galleries.',
                ],
            ],
            [
                'sort_order' => 3,
                'config'     => ['image' => $this->mediaReference('https://picsum.photos/id/1052/1200/900')],
                'es' => [
                    'alt'       => 'Vista inline 3',
                    'caption'   => 'La jerarquía del preview guía la atención.',
                ],
                'en' => [
                    'alt'       => 'Inline preview 3',
                    'caption'   => 'The preview hierarchy guides attention.',
                ],
            ],
        ], $blockIds, $langIds);

        $this->upsertBlock(
            $pageId,
            $blockIds,
            'rich_text',
            7,
            ['css_class' => ''],
            [
                'es' => [
                    'content' => '<h2>Vista modal</h2><p>Cuando la imagen necesita más foco, el modal permite navegar sin perder el contexto de la grilla. Es la opción más útil para colecciones visuales con material ampliable.</p>',
                ],
                'en' => [
                    'content' => '<h2>Modal view</h2><p>When an image needs more focus, the modal lets you navigate without losing the grid context. It is the best option for visual collections with expandable material.</p>',
                ],
            ],
            $langIds
        );

        $modalGalleryId = $this->upsertBlock(
            $pageId,
            $blockIds,
            'gallery',
            8,
            ['presentation_mode' => 'modal_preview', 'columns' => '3', 'gap' => 'large', 'css_class' => 'my-10'],
            ['es' => [], 'en' => []],
            $langIds
        );
        $this->seedChildBlocks($pageId, $modalGalleryId, 'gallery_item', [
            [
                'sort_order' => 1,
                'config'     => ['image' => $this->mediaReference('https://picsum.photos/id/1060/1200/900')],
                'es' => [
                    'alt'       => 'Vista modal 1',
                    'caption'   => 'El modal resalta una pieza a la vez.',
                    'link_url'  => '/es/portafolio',
                    'link_label' => 'Ver portafolio',
                ],
                'en' => [
                    'alt'       => 'Modal view 1',
                    'caption'   => 'The modal highlights one piece at a time.',
                    'link_url'  => '/en/portfolio',
                    'link_label' => 'View portfolio',
                ],
            ],
            [
                'sort_order' => 2,
                'config'     => ['image' => $this->mediaReference('https://picsum.photos/id/1061/1200/900')],
                'es' => [
                    'alt'       => 'Vista modal 2',
                    'caption'   => 'Ideal para fotografía, posters y material editorial.',
                ],
                'en' => [
                    'alt'       => 'Modal view 2',
                    'caption'   => 'Ideal for photography, posters, and editorial material.',
                ],
            ],
            [
                'sort_order' => 3,
                'config'     => ['image' => $this->mediaReference('https://picsum.photos/id/1062/1200/900')],
                'es' => [
                    'alt'       => 'Vista modal 3',
                    'caption'   => 'El modal preserva el foco visual sin recargar la página.',
                ],
                'en' => [
                    'alt'       => 'Modal view 3',
                    'caption'   => 'The modal preserves visual focus without overloading the page.',
                ],
            ],
        ], $blockIds, $langIds);

        $this->upsertBlock(
            $pageId,
            $blockIds,
            'video_player',
            9,
            ['autoplay' => false, 'mute' => false, 'loop' => false, 'aspect_ratio' => '16/9', 'css_class' => 'my-12'],
            [
                'es' => [
                    'heading'   => 'Video de demostración',
                    'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                ],
                'en' => [
                    'heading'   => 'Demo video',
                    'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                ],
            ],
            $langIds
        );

        // ── 10. document_gallery ─────────────────────────────────────────────
        $this->upsertBlock(
            $pageId,
            $blockIds,
            'document_gallery',
            10,
            ['layout' => 'grid_cards', 'show_file_meta' => true, 'open_in_new_tab' => true, 'css_class' => 'my-12'],
            [
                'es' => [
                    'title'       => 'Repositorio de Documentos',
                    'description' => 'Listado de descargas con iconos adaptativos según extensión de archivo (PDF, Word, ZIP, Excel).',
                    'documents'   => [
                        [
                            'file'        => $this->mediaReference('http://localhost:8186/assets/docs/policies-handbook-demo.pdf'),
                            'title'       => 'Manual de Políticas Generales',
                            'description' => 'Documento PDF que describe los lineamientos y políticas fundamentales.',
                        ],
                        [
                            'file'        => $this->mediaReference('https://calibre-ebook.com/downloads/demos/demo.docx'),
                            'title'       => 'Formulario de Afiliación',
                            'description' => 'Plantilla de Word editable para completar tus datos.',
                        ],
                        [
                            'file'        => $this->mediaReference('https://www.learningcontainer.com/wp-content/uploads/2020/05/sample-zip-file.zip'),
                            'title'       => 'Paquete de Recursos Gráficos',
                            'description' => 'Archivo ZIP comprimido con logos oficiales, manual de marca e iconos.',
                        ],
                    ],
                ],
                'en' => [
                    'title'       => 'Document Repository',
                    'description' => 'Download list with adaptive icons matching file extensions (PDF, Word, ZIP, Excel).',
                    'documents'   => [
                        [
                            'file'        => $this->mediaReference('http://localhost:8186/assets/docs/policies-handbook-demo.pdf'),
                            'title'       => 'General Policies Handbook',
                            'description' => 'PDF document covering the core guidelines and company rules.',
                        ],
                        [
                            'file'        => $this->mediaReference('https://calibre-ebook.com/downloads/demos/demo.docx'),
                            'title'       => 'Affiliation Form',
                            'description' => 'Editable Word document template to fill in your personal data.',
                        ],
                        [
                            'file'        => $this->mediaReference('https://www.learningcontainer.com/wp-content/uploads/2020/05/sample-zip-file.zip'),
                            'title'       => 'Brand Asset Bundle',
                            'description' => 'ZIP compressed package containing official logos, styleguides, and icons.',
                        ],
                    ],
                ],
            ],
            $langIds
        );

        // ── 11. pdf_viewer ───────────────────────────────────────────────────
        $this->upsertBlock(
            $pageId,
            $blockIds,
            'pdf_viewer',
            11,
            [
                'pdf_file'      => $this->mediaReference('http://localhost:8186/assets/docs/policies-handbook-demo.pdf'),
                'height'        => '600px',
                'allow_download' => true,
                'css_class'     => 'my-12',
            ],
            [
                'es' => [
                    'heading' => 'Previsualización del Manual de Políticas',
                ],
                'en' => [
                    'heading' => 'Policies Handbook Preview',
                ],
            ],
            $langIds
        );

        // ── 12. external_links ───────────────────────────────────────────────
        $this->upsertBlock(
            $pageId,
            $blockIds,
            'external_links',
            12,
            ['layout_columns' => '3', 'open_in_new_tab' => true, 'css_class' => 'my-12'],
            [
                'es' => [
                    'title'       => 'Directorio de Sitios Recomendados',
                    'description' => 'Enlaces útiles a recursos externos y portales oficiales del ecosistema.',
                    'links'       => [
                        [
                            'label'       => 'Documentación Oficial',
                            'url'         => 'https://codeigniter.com',
                            'description' => 'Sitio web principal y manuales técnicos de CodeIgniter 4.',
                            'icon_name'   => 'book-open',
                        ],
                        [
                            'label'       => 'Repositorio GitHub',
                            'url'         => 'https://github.com',
                            'description' => 'Acceso al código fuente, incidencias y control de versiones.',
                            'icon_name'   => 'github',
                        ],
                        [
                            'label'       => 'Canal de Soporte',
                            'url'         => 'https://slack.com',
                            'description' => 'Comunidad y salas de chat en vivo para soporte técnico.',
                            'icon_name'   => 'message-square',
                        ],
                    ],
                ],
                'en' => [
                    'title'       => 'Recommended Sites Directory',
                    'description' => 'Useful links to external resources and official ecosystem portals.',
                    'links'       => [
                        [
                            'label'       => 'Official Documentation',
                            'url'         => 'https://codeigniter.com',
                            'description' => 'Main website and technical user guides for CodeIgniter 4.',
                            'icon_name'   => 'book-open',
                        ],
                        [
                            'label'       => 'GitHub Repository',
                            'url'         => 'https://github.com',
                            'description' => 'Access to source code, issue tracking, and version history.',
                            'icon_name'   => 'github',
                        ],
                        [
                            'label'       => 'Support Chat Channel',
                            'url'         => 'https://slack.com',
                            'description' => 'Community chatrooms for live technical support and discussion.',
                            'icon_name'   => 'message-square',
                        ],
                    ],
                ],
            ],
            $langIds
        );

        // ── 13. cta ──────────────────────────────────────────────────────────
        $this->upsertBlock(
            $pageId,
            $blockIds,
            'cta',
            13,
            ['variant' => 'blue', 'css_class' => ''],
            [
                'es' => [
                    'heading' => '¿Quieres revisar otra página demo?',
                    'text'    => 'Pasa por el portafolio, las noticias o quiénes somos para ver más combinaciones de bloques funcionando en vivo.',
                    'label'   => 'Ir al portafolio',
                    'url'     => '/es/portafolio',
                ],
                'en' => [
                    'heading' => 'Want to review another demo page?',
                    'text'    => 'Visit the portfolio, news, or about pages to see more block combinations working live.',
                    'label'   => 'Go to portfolio',
                    'url'     => '/en/portfolio',
                ],
            ],
            $langIds
        );
    }

    private function upsertPage(): int
    {
        // Create a dedicated demo page instead of reusing the first generic row.
        // The bootstrap seeds many generic pages, so a wide upsert would be
        // unstable and could overwrite a different demo page.
        $existing = $this->db->table('cms_pages')
            ->select('cms_pages.id')
            ->join('cms_page_translations', 'cms_page_translations.page_id = cms_pages.id')
            ->where('cms_pages.deleted_at IS NULL', null, false)
            ->whereIn('cms_page_translations.slug', ['multimedia', 'media'])
            ->orderBy('cms_pages.id', 'ASC')
            ->get()
            ->getRowArray();

        if ($existing !== null) {
            $pageId = (int) $existing['id'];
            $updatePayload = [
                'page_type'          => 'generic',
                'status'             => 'published',
                'published_at'       => date('Y-m-d H:i:s'),
                'scheduled_at'       => null,
                'sort_order'         => 35,
                'sitemap_priority'   => '0.6',
                'sitemap_changefreq' => 'monthly',
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
                'sort_order'         => 35,
                'sitemap_priority'   => '0.6',
                'sitemap_changefreq' => 'monthly',
                'is_in_sitemap'      => 1,
                'deleted_at'         => null,
            ]);
        }

        if ($pageId === null) {
            throw new \RuntimeException('SiteMediaPageSeeder: unable to seed media page.');
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
            echo "SiteMediaPageSeeder: block type '{$blockKey}' not found — skipped.\n";
            return 0;
        }

        $instanceId = $this->upsertRecord('cms_block_instances', [
            'block_id'           => $blockId,
            'owner_type'         => 'page',
            'owner_id'           => $pageId,
            'parent_instance_id' => $parentInstanceId,
            'sort_order'         => $sortOrder,
        ], [
            'column_index' => null,
            'is_active'    => 1,
            'block_config' => json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
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
                'column_index' => null,
                'is_active'    => 1,
                'block_config' => json_encode(
                    is_array($item['config'] ?? null) ? $item['config'] : [],
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                ),
            ]);

            if ($instanceId === null) {
                continue;
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
}
