<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use App\Database\Seeds\Concerns\CollectionBlockPresets;
use App\Database\Seeds\Concerns\IdempotentSeederSupport;
use CodeIgniter\Database\Seeder;

/**
 * Seeds the starter site's news collection, taxonomy, and sample entries.
 * Idempotent across repeated bootstrap runs.
 */
class NewsCollectionSeeder extends Seeder
{
    use IdempotentSeederSupport;

    public function run(): void
    {
        $langIds = $this->langIds(['es', 'en']);

        if (empty($langIds['es'])) {
            echo "NewsCollectionSeeder: 'es' language not found in cms_languages. Seed CmsLanguageSeeder first.\n";
            return;
        }

        // $this->db->transStart();
        $preset = CollectionBlockPresets::news();

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

        $catIdMap = $this->seedCategories($collectionId, $langIds);
        $this->seedSampleEntries($collectionId, $langIds, $catIdMap);

        echo "NewsCollectionSeeder: 'noticias' collection seeded successfully (collection_id={$collectionId}).\n";
        return;
    }

    /**
     * Seeds the two news categories and returns a slug => category_id map.
     *
     * @param array<string, int> $langIds
     * @return array<string, int>
     */
    private function seedCategories(int $collectionId, array $langIds): array
    {
        $categories = [
            ['es' => ['name' => 'Producto', 'slug' => 'producto'], 'en' => ['name' => 'Product', 'slug' => 'product']],
            ['es' => ['name' => 'Compañía', 'slug' => 'compania'], 'en' => ['name' => 'Company', 'slug' => 'company']],
        ];

        $catIdMap = [];
        foreach ($categories as $index => $cat) {
            $catId = $this->upsertRecord('cms_categories', [
                'collection_id' => $collectionId,
                'sort_order'    => $index + 1,
            ], [
                'parent_id' => null,
                'is_active' => 1,
            ]);

            if ($catId === null) {
                continue;
            }

            foreach ($cat as $langCode => $trans) {
                $langId = $langIds[$langCode] ?? null;
                if ($langId === null) {
                    continue;
                }
                $this->upsertRecord('cms_category_translations', [
                    'category_id' => $catId,
                    'language_id' => $langId,
                ], [
                    'name' => $trans['name'],
                    'slug' => $trans['slug'],
                ]);
            }

            $catIdMap[$cat['es']['slug']] = $catId;
        }

        return $catIdMap;
    }

    /**
     * @param array<string, int> $langIds
     * @param array<string, int> $catIdMap
     */
    private function seedSampleEntries(int $collectionId, array $langIds, array $catIdMap = []): void
    {
        $newsEntries = [
            [
                'sort_order'         => 1,
                'published_days_ago' => 2,
                'category_slug'      => 'producto',
                'featured_image'     => $this->mediaReference('https://picsum.photos/id/1011/1200/800'),
                'detail_image'       => $this->mediaReference('https://picsum.photos/id/1025/1200/800'),
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
                'published_days_ago' => 1,
                'category_slug'      => 'producto',
                'featured_image'     => $this->mediaReference('https://picsum.photos/id/1015/1200/800'),
                'detail_image'       => $this->mediaReference('https://picsum.photos/id/1035/1200/800'),
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
            [
                'sort_order'         => 3,
                'published_days_ago' => 0,
                'category_slug'      => 'compania',
                'featured_image'     => $this->mediaReference('https://picsum.photos/id/1043/1200/800'),
                'detail_image'       => $this->mediaReference('https://picsum.photos/id/1044/1200/800'),
                'es' => [
                    'title'            => 'Ahora cada noticia sugiere historias relacionadas',
                    'slug'             => 'historias-relacionadas',
                    'excerpt'          => 'Al final de cada entrada mostramos otras noticias de la misma categoría para seguir explorando.',
                    'meta_title'       => 'Historias relacionadas | Noticias',
                    'meta_description' => 'Las entradas ahora enlazan a otras noticias relacionadas por categoría.',
                    'rich_text'        => '<p>Cada entrada del sitio ahora cierra con una sección de historias relacionadas, calculada a partir de la categoría que comparte con otras noticias.</p><h2>Contenido que invita a seguir leyendo</h2><p>Cuando no hay suficientes noticias en la misma categoría, el sistema completa la lista con las publicaciones más recientes de la colección, así la sección nunca queda vacía.</p><ul><li>Selección automática por categoría compartida.</li><li>Alternativa segura cuando falta contenido relacionado.</li><li>Botones para compartir la noticia en redes o copiar el enlace.</li></ul><p>El objetivo es que cada historia sea también una puerta de entrada a las demás.</p>',
                ],
                'en' => [
                    'title'            => 'Every story now surfaces related reads',
                    'slug'             => 'related-stories',
                    'excerpt'          => 'Each entry now closes with other news from the same category so readers keep exploring.',
                    'meta_title'       => 'Related stories | News',
                    'meta_description' => 'Entries now link to other related news by category.',
                    'rich_text'        => '<p>Every entry on the site now ends with a related-stories section, computed from the category it shares with other news items.</p><h2>Content that invites you to keep reading</h2><p>When there aren\'t enough stories in the same category, the system fills the list with the most recent entries in the collection, so the section is never empty.</p><ul><li>Automatic selection by shared category.</li><li>A safe fallback when related content is scarce.</li><li>Buttons to share the story on social media or copy the link.</li></ul><p>The goal is for every story to also be a doorway into the others.</p>',
                ],
            ],
        ];

        $blockIds = $this->blockIds(['rich_text', 'image']);
        if (! isset($blockIds['rich_text'], $blockIds['image'])) {
            return;
        }

        foreach ($newsEntries as $entry) {
            $publishedAt = date('Y-m-d H:i:s', strtotime('-' . (int) $entry['published_days_ago'] . ' days'));

            $entryId = $this->upsertRecord('cms_entries', [
                'collection_id' => $collectionId,
                'sort_order'    => $entry['sort_order'],
            ], [
                'workflow_status' => 'published',
                'is_featured'     => 1,
                'published_at'    => $publishedAt,
            ]);

            if ($entryId === null) {
                continue;
            }

            $catId = $catIdMap[$entry['category_slug']] ?? null;
            if ($catId !== null) {
                $this->upsertRecord('cms_entry_categories', [
                    'entry_id'    => $entryId,
                    'category_id' => $catId,
                ], []);
            }

            $imageBlockId = $this->upsertRecord('cms_block_instances', [
                'block_id'   => $blockIds['image'],
                'owner_type' => 'entry',
                'owner_id'   => $entryId,
                'sort_order' => 1,
            ], [
                'is_active'    => 1,
                'block_config' => json_encode(
                    ['image' => $entry['detail_image']],
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                ),
            ]);

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
                $featuredImageColumns = $this->mediaReferenceColumns(
                    $entry['featured_image'] ?? null,
                    'featured_file_id',
                    'featured_image_url'
                );
                $this->upsertRecord('cms_entry_translations', [
                    'entry_id'    => $entryId,
                    'language_id' => $langId,
                ], [
                    'title'              => $translation['title'],
                    'slug'               => $translation['slug'],
                    'excerpt'            => $translation['excerpt'],
                    'featured_file_id'   => $featuredImageColumns['featured_file_id'],
                    'featured_image_url' => $featuredImageColumns['featured_image_url'],
                    'meta_title'         => $translation['meta_title'],
                    'meta_description'   => $translation['meta_description'],
                ]);

                if ($imageBlockId !== null) {
                    $this->upsertRecord('cms_block_instance_translations', [
                        'instance_id' => $imageBlockId,
                        'language_id' => $langId,
                    ], [
                        'block_data' => json_encode([
                            'alt'     => $translation['title'],
                            'caption' => $translation['title'],
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

            // Seeded news entries are article content: a headline and a cover
            // image. The article template already renders the breadcrumb,
            // title, category badges, date, and related-stories section, so
            // entries do not need their own page_header/hero_banner/cta
            // blocks — those are landing-page building blocks and would
            // duplicate what the template already shows.
            $keptInstanceIds = array_values(array_filter([$imageBlockId, $textBlockId]));
            $this->cleanupStaleEntryBlocks($entryId, $keptInstanceIds);
        }
    }

    /** @param list<int> $keptInstanceIds */
    private function cleanupStaleEntryBlocks(int $entryId, array $keptInstanceIds): void
    {
        if ($keptInstanceIds === []) {
            return;
        }

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

}
