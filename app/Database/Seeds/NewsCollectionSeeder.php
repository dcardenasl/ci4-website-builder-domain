<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use App\Database\Seeds\Concerns\IdempotentSeederSupport;
use CodeIgniter\Database\Seeder;

/**
 * Seeds the starter site's news collection and its public collection index page.
 * Idempotent across repeated bootstrap runs.
 */
class NewsCollectionSeeder extends Seeder
{
    use IdempotentSeederSupport;

    public function run(): void
    {
        $existing = $this->db->table('cms_collections')
            ->where('collection_key', 'noticias')
            ->get()
            ->getRowArray();

        $langIds = $this->langIds(['es', 'en']);

        if (empty($langIds['es'])) {
            echo "NewsCollectionSeeder: 'es' language not found in cms_languages. Seed CmsLanguageSeeder first.\n";
            return;
        }

        if ($existing !== null) {
            $collectionId = (int) $existing['id'];
            $newsPageId = $this->upsertCollectionIndexPage($collectionId);
            if ($newsPageId !== null) {
                $this->upsertCollectionIndexTranslation($newsPageId, $langIds['es'] ?? null, [
                    'slug'             => 'noticias',
                    'title'            => 'Noticias',
                    'excerpt'          => 'Mantente al día con las noticias y novedades del sitio.',
                    'meta_title'       => 'Noticias | Mi Sitio',
                    'meta_description' => 'Explora el índice público de noticias y actualizaciones.',
                ]);
                $this->upsertCollectionIndexTranslation($newsPageId, $langIds['en'] ?? null, [
                    'slug'             => 'news',
                    'title'            => 'News',
                    'excerpt'          => 'Stay up to date with the site news and updates.',
                    'meta_title'       => 'News | My Site',
                    'meta_description' => 'Explore the public index of news and updates.',
                ]);
            }

            $this->seedSampleEntries($collectionId, $langIds);

            echo "NewsCollectionSeeder: 'noticias' collection already exists, repaired/ensured collection index page.\n";
            return;
        }

        // $this->db->transStart();
        $preset = [
            'block_template' => [
                'version' => '1.0',
                'blocks' => [
                    [
                        'block_key' => 'rich_text',
                        'label' => 'Titular',
                        'help_text' => 'Bloque principal de la noticia',
                        'required' => true,
                        'locked' => true,
                        'block_config_defaults' => new \stdClass(),
                        'sort_order' => 1,
                    ],
                    [
                        'block_key' => 'image',
                        'label' => 'Imagen de portada',
                        'help_text' => 'Acompaña la noticia con una imagen',
                        'required' => false,
                        'locked' => false,
                        'block_config_defaults' => new \stdClass(),
                        'sort_order' => 2,
                    ],
                    [
                        'block_key' => 'page_header',
                        'label' => 'Encabezado editorial',
                        'help_text' => 'Presenta la entrada con contexto y jerarquía visual',
                        'required' => false,
                        'locked' => false,
                        'block_config_defaults' => new \stdClass(),
                        'sort_order' => 3,
                    ],
                    [
                        'block_key' => 'hero_banner',
                        'label' => 'Hero de la noticia',
                        'help_text' => 'Destaca la historia con imagen, bajada y llamada a la acción',
                        'required' => false,
                        'locked' => false,
                        'block_config_defaults' => new \stdClass(),
                        'sort_order' => 4,
                    ],
                    [
                        'block_key' => 'cta',
                        'label' => 'Cierre y llamada a la acción',
                        'help_text' => 'Invita a explorar más contenido del sitio',
                        'required' => false,
                        'locked' => false,
                        'block_config_defaults' => new \stdClass(),
                        'sort_order' => 5,
                    ],
                    [
                        'block_key' => 'alert',
                        'label' => 'Dato destacado',
                        'help_text' => 'Resalta una idea, cifra o aprendizaje de la noticia',
                        'required' => false,
                        'locked' => false,
                        'block_config_defaults' => new \stdClass(),
                        'sort_order' => 6,
                    ],
                ],
            ],
            'wizard_config' => [
                'type' => 'news',
                'steps' => [
                    ['step_title' => 'Titular', 'step_hint' => 'Título visible para la noticia', 'fields' => [['key' => 'title', 'label' => 'Titular', 'type' => 'text', 'required' => true]]],
                    ['step_title' => 'Resumen', 'step_hint' => 'Una breve bajada informativa', 'fields' => [['key' => 'excerpt', 'label' => 'Resumen', 'type' => 'textarea', 'required' => false]]],
                ],
            ],
        ];

        // ── 1. Collection ──────────────────────────────────────────────────────
        $collectionPayload = [
            'collection_key'           => 'noticias',
            'collection_type'          => 'news',
            'is_active'                => 1,
            'requires_approval'        => 0,
            'enables_categories'       => 1,
            'enables_tags'             => 1,
            'default_sitemap_priority' => '0.70',
            'default_changefreq'       => 'weekly',
            'sort_order'               => 10,
            'block_template'           => json_encode($preset['block_template'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'wizard_config'            => json_encode($preset['wizard_config'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'created_at'               => date('Y-m-d H:i:s'),
            'updated_at'               => date('Y-m-d H:i:s'),
        ];

        $collectionId = $this->upsertRecord('cms_collections', [
            'collection_key' => 'noticias',
        ], $collectionPayload);

        if ($collectionId === null) {
            echo "NewsCollectionSeeder: unable to seed 'noticias' collection.\n";
            return;
        }

        // ── 2. Collection translations ─────────────────────────────────────────
        $collectionTranslations = [
            'es' => [
                'slug'                     => 'noticias',
                'name'                     => 'Noticias',
                'description'              => 'Sección de noticias y actualidad.',
                'listing_title'            => 'Últimas Noticias',
                'listing_intro'            => 'Mantente al día con todo lo que sucede.',
                'default_meta_title'       => 'Noticias | Mi Sitio',
                'default_meta_description' => 'Lee las últimas noticias y actualizaciones.',
            ],
            'en' => [
                'slug'                     => 'news',
                'name'                     => 'News',
                'description'              => 'News and current events section.',
                'listing_title'            => 'Latest News',
                'listing_intro'            => 'Stay up to date with everything happening.',
                'default_meta_title'       => 'News | My Site',
                'default_meta_description' => 'Read the latest news and updates.',
            ],
        ];

        foreach ($collectionTranslations as $code => $trans) {
            $langId = $langIds[$code] ?? null;
            if ($langId === null) {
                continue;
            }
            $this->upsertRecord('cms_collection_translations', [
                'collection_id' => $collectionId,
                'language_id'   => $langId,
            ], $trans);
        }

        $this->seedSampleEntries($collectionId, $langIds);

        $newsPageId = $this->upsertCollectionIndexPage($collectionId);
        if ($newsPageId !== null) {
            $this->upsertCollectionIndexTranslation($newsPageId, $langIds['es'] ?? null, [
                'slug'             => 'noticias',
                'title'            => 'Noticias',
                'excerpt'          => 'Mantente al día con las noticias y novedades del sitio.',
                'meta_title'       => 'Noticias | Mi Sitio',
                'meta_description' => 'Explora el índice público de noticias y actualizaciones.',
            ]);
            $this->upsertCollectionIndexTranslation($newsPageId, $langIds['en'] ?? null, [
                'slug'             => 'news',
                'title'            => 'News',
                'excerpt'          => 'Stay up to date with the site news and updates.',
                'meta_title'       => 'News | My Site',
                'meta_description' => 'Explore the public index of news and updates.',
            ]);
        }

        echo "NewsCollectionSeeder: 'noticias' collection seeded successfully (collection_id={$collectionId}, index page ensured).\n";
        return;
    }

    /**
     * @param array<string, int> $langIds
     */
    private function seedSampleEntries(int $collectionId, array $langIds): void
    {
        $newsEntries = [
            [
                'sort_order'         => 1,
                'featured_image_url' => 'https://picsum.photos/id/1011/1200/800',
                'detail_image_url'   => 'https://picsum.photos/id/1025/1200/800',
                'es' => [
                    'title'            => 'Lanzamos el nuevo portal editorial',
                    'slug'             => 'nuevo-portal-editorial',
                    'excerpt'          => 'Publicamos una experiencia editorial renovada, con mejor lectura y navegación más clara.',
                    'meta_title'       => 'Nuevo portal editorial | Noticias',
                    'meta_description' => 'Descubre el nuevo portal editorial y sus mejoras de lectura.',
                    'rich_text'        => '<p>El portal editorial se renovó para ofrecer una navegación más limpia, tarjetas con imagen y una jerarquía visual más consistente.</p><h2>Una experiencia pensada para leer</h2><p>El nuevo recorrido combina titulares claros, resúmenes precisos y recursos visuales que ayudan a entender cada historia antes de abrirla.</p><ul><li>Portadas adaptadas a cada formato de pantalla.</li><li>Contenido bilingüe con URLs localizadas.</li><li>Componentes reutilizables para escalar nuevas secciones.</li></ul><p>La nueva presentación mejora la lectura en pantallas grandes y móviles sin perder contexto del contenido.</p>',
                ],
                'en' => [
                    'title'            => 'We launched the new editorial portal',
                    'slug'             => 'new-editorial-portal',
                    'excerpt'          => 'We released a refreshed editorial experience with clearer reading flow and navigation.',
                    'meta_title'       => 'New editorial portal | News',
                    'meta_description' => 'Discover the new editorial portal and its reading improvements.',
                    'rich_text'        => '<p>The editorial portal was refreshed to provide clearer navigation, image-backed cards, and a more consistent visual hierarchy.</p><h2>An experience designed for reading</h2><p>The new journey combines clear headlines, precise summaries, and visual resources that help readers understand each story before opening it.</p><ul><li>Responsive cover images for every screen size.</li><li>Bilingual content with localized URLs.</li><li>Reusable components that make new sections easy to scale.</li></ul><p>The new layout improves readability on large and small screens without losing content context.</p>',
                ],
            ],
            [
                'sort_order'         => 2,
                'featured_image_url' => 'https://picsum.photos/id/1015/1200/800',
                'detail_image_url'   => 'https://picsum.photos/id/1035/1200/800',
                'es' => [
                    'title'            => 'La colección de noticias ahora destaca portadas',
                    'slug'             => 'noticias-destacan-portadas',
                    'excerpt'          => 'Cada tarjeta del listado público puede mostrar una portada destacada si la entrada la tiene configurada.',
                    'meta_title'       => 'Noticias con portada | Noticias',
                    'meta_description' => 'Las tarjetas del listado ahora muestran portadas destacadas cuando existen.',
                    'rich_text'        => '<p>Las noticias del starter ahora incluyen imágenes de portada reales para que el grid de inicio no se vea vacío o incompleto.</p><h2>Diseño editorial con flexibilidad</h2><p>La portada es sólo el comienzo: cada entrada combina una imagen, una bajada, contenido enriquecido y llamadas a la acción que pueden cambiar sin tocar la plantilla.</p><ul><li>Tarjetas con imagen para destacar historias.</li><li>Fallback seguro cuando una entrada no tiene portada.</li><li>Filtros y listados preparados para crecer.</li></ul><p>Así el mismo sistema puede presentar una noticia breve, un anuncio de producto o una historia de largo formato.</p>',
                ],
                'en' => [
                    'title'            => 'News now highlights cover images',
                    'slug'             => 'news-highlights-cover-images',
                    'excerpt'          => 'Each public listing card can show a featured cover when the entry has one configured.',
                    'meta_title'       => 'News with cover image | News',
                    'meta_description' => 'Listing cards now show featured cover images when available.',
                    'rich_text'        => '<p>The starter news items now include real cover images so the home grid no longer feels empty or incomplete.</p><h2>Editorial design with flexibility</h2><p>The cover is only the beginning: every entry combines an image, a summary, rich content, and calls to action that can change without touching the template.</p><ul><li>Image-backed cards for standout stories.</li><li>A safe fallback when an entry has no cover.</li><li>Filters and listings ready to grow.</li></ul><p>The same system can present a short update, a product announcement, or a long-form story.</p>',
                ],
            ],
        ];

        $blockIds = $this->blockIds(['rich_text', 'image']);
        if (! isset($blockIds['rich_text'], $blockIds['image'])) {
            return;
        }

        foreach ($newsEntries as $entry) {
            $entryId = $this->upsertRecord('cms_entries', [
                'collection_id' => $collectionId,
                'sort_order'    => $entry['sort_order'],
            ], [
                'workflow_status' => 'published',
                'is_featured'     => 1,
                'published_at'    => date('Y-m-d H:i:s'),
            ]);

            if ($entryId === null) {
                continue;
            }

            $imageBlockId = $this->upsertRecord('cms_block_instances', [
                'block_id'   => $blockIds['image'],
                'owner_type' => 'entry',
                'owner_id'   => $entryId,
                'sort_order' => 1,
            ], ['is_active' => 1]);

            $textBlockId = $this->upsertRecord('cms_block_instances', [
                'block_id'   => $blockIds['rich_text'],
                'owner_type' => 'entry',
                'owner_id'   => $entryId,
                'sort_order' => 2,
            ], ['is_active' => 1]);

            foreach (['es', 'en'] as $langCode) {
                $langId = $langIds[$langCode] ?? null;
                if ($langId === null) {
                    continue;
                }

                $translation = $entry[$langCode];
                $this->upsertRecord('cms_entry_translations', [
                    'entry_id'    => $entryId,
                    'language_id' => $langId,
                ], [
                    'title'              => $translation['title'],
                    'slug'               => $translation['slug'],
                    'excerpt'            => $translation['excerpt'],
                    'featured_image_url' => $entry['featured_image_url'],
                    'meta_title'         => $translation['meta_title'],
                    'meta_description'   => $translation['meta_description'],
                ]);

                if ($imageBlockId !== null) {
                    $this->upsertRecord('cms_block_instance_translations', [
                        'instance_id' => $imageBlockId,
                        'language_id' => $langId,
                    ], [
                        'block_data' => json_encode([
                            'image_url' => $entry['detail_image_url'],
                            'alt'       => $translation['title'],
                            'caption'   => $translation['title'],
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ]);
                }

                if ($textBlockId !== null) {
                    $this->upsertRecord('cms_block_instance_translations', [
                        'instance_id' => $textBlockId,
                        'language_id' => $langId,
                    ], [
                        'block_data' => json_encode([
                            'content' => $translation['rich_text'],
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ]);
                }
            }

            // Additional editorial blocks make the seeded entry a complete,
            // bilingual showcase of the dynamic content system.
            $extraBlocks = [
                'page_header' => [
                    'es' => [
                        'heading' => 'Detrás de la historia',
                        'subheading' => 'El contexto, las decisiones y los aprendizajes detrás de esta actualización.',
                        'breadcrumb_label' => 'Noticias',
                        'breadcrumb_url' => '/noticias',
                    ],
                    'en' => [
                        'heading' => 'Behind the story',
                        'subheading' => 'The context, decisions, and lessons behind this update.',
                        'breadcrumb_label' => 'News',
                        'breadcrumb_url' => '/news',
                    ],
                ],
                'hero_banner' => [
                    'es' => [
                        'image_url' => $entry['detail_image_url'],
                        'alt' => $entry['es']['title'],
                        'heading' => 'Una experiencia editorial renovada',
                        'subheading' => $entry['es']['excerpt'],
                        'cta_label' => 'Explorar más historias',
                        'cta_url' => '/noticias',
                    ],
                    'en' => [
                        'image_url' => $entry['detail_image_url'],
                        'alt' => $entry['en']['title'],
                        'heading' => 'A refreshed editorial experience',
                        'subheading' => $entry['en']['excerpt'],
                        'cta_label' => 'Explore more stories',
                        'cta_url' => '/news',
                    ],
                ],
                'cta' => [
                    'es' => [
                        'heading' => '¿Quieres seguir descubriendo?',
                        'text' => 'Revisa todas las noticias y encuentra nuevas ideas para tu próximo proyecto.',
                        'label' => 'Ver todas las noticias',
                        'url' => '/noticias',
                    ],
                    'en' => [
                        'heading' => 'Want to keep exploring?',
                        'text' => 'Browse all news and find fresh ideas for your next project.',
                        'label' => 'View all news',
                        'url' => '/news',
                    ],
                ],
                'alert' => [
                    'es' => [
                        'title' => 'Idea clave',
                        'message' => '<p>El contenido y la presentación viven en bloques independientes: el equipo editorial puede cambiar el orden, el diseño o el llamado a la acción sin rehacer la entrada.</p>',
                    ],
                    'en' => [
                        'title' => 'Key idea',
                        'message' => '<p>Content and presentation live in independent blocks: editors can change the order, design, or call to action without rebuilding the entry.</p>',
                    ],
                ],
            ];
            $extraBlockIds = $this->blockIds(array_keys($extraBlocks));
            $keptInstanceIds = array_values(array_filter([$imageBlockId, $textBlockId]));
            foreach ($extraBlocks as $blockKey => $translations) {
                if (! isset($extraBlockIds[$blockKey])) {
                    continue;
                }
                $instanceId = $this->upsertRecord('cms_block_instances', [
                    'block_id' => $extraBlockIds[$blockKey],
                    'owner_type' => 'entry',
                    'owner_id' => $entryId,
                    'sort_order' => ['page_header' => 3, 'hero_banner' => 4, 'cta' => 5, 'alert' => 6][$blockKey],
                ], ['is_active' => 1, 'block_config' => json_encode(match ($blockKey) {
                    'cta' => ['variant' => 'blue'],
                    'alert' => ['type' => 'info', 'dismissible' => false],
                    'page_header' => ['bg_color' => 'bg-slate-50'],
                    'hero_banner' => ['overlay_color' => 'rgba(15, 23, 42, 0.55)'],
                    default => [],
                })]);
                if ($instanceId === null) {
                    continue;
                }
                $keptInstanceIds[] = $instanceId;
                foreach ($translations as $langCode => $blockData) {
                    $langId = $langIds[$langCode] ?? null;
                    if ($langId === null) {
                        continue;
                    }
                    $this->upsertRecord('cms_block_instance_translations', [
                        'instance_id' => $instanceId,
                        'language_id' => $langId,
                    ], ['block_data' => json_encode($blockData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
                }
            }

            if (count($keptInstanceIds) === 6) {
                $this->cleanupStaleEntryBlocks($entryId, $keptInstanceIds);
            }
        }
    }

    /** @param list<int> $keptInstanceIds */
    private function cleanupStaleEntryBlocks(int $entryId, array $keptInstanceIds): void
    {
        $stale = $this->db->table('cms_block_instances')
            ->select('id')
            ->where('owner_type', 'entry')
            ->where('owner_id', $entryId)
            ->whereNotIn('id', $keptInstanceIds)
            ->get()
            ->getResultArray();

        foreach ($stale as $instance) {
            $this->db->table('cms_block_instances')
                ->where('id', (int) $instance['id'])
                ->delete();
        }
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

    /** @param array<string, string> $langIds */
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

    private function upsertCollectionIndexPage(int $collectionId): ?int
    {
        return $this->upsertCollectionIndexPageRecord($collectionId, ['news'], [
            'status'             => 'published',
            'published_at'       => date('Y-m-d H:i:s'),
            'scheduled_at'       => null,
            'sort_order'         => 30,
            'sitemap_priority'   => '0.8',
            'sitemap_changefreq' => 'weekly',
            'is_in_sitemap'      => 1,
            'deleted_at'         => null,
        ]);
    }

    private function upsertCollectionIndexTranslation(?int $pageId, ?int $languageId, array $translationData): void
    {
        if ($pageId === null || $languageId === null) {
            return;
        }

        $this->upsertRecord('cms_page_translations', [
            'page_id'     => $pageId,
            'language_id' => $languageId,
        ], $translationData);
    }

}
