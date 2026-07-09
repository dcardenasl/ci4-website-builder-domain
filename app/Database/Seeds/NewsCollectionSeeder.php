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
                'es' => [
                    'title'            => 'Lanzamos el nuevo portal editorial',
                    'slug'             => 'nuevo-portal-editorial',
                    'excerpt'          => 'Publicamos una experiencia editorial renovada, con mejor lectura y navegación más clara.',
                    'meta_title'       => 'Nuevo portal editorial | Noticias',
                    'meta_description' => 'Descubre el nuevo portal editorial y sus mejoras de lectura.',
                    'rich_text'        => '<p>El portal editorial se renovó para ofrecer una navegación más limpia, tarjetas con imagen y una jerarquía visual más consistente.</p><p>La nueva presentación mejora la lectura en pantallas grandes y móviles sin perder contexto del contenido.</p>',
                ],
                'en' => [
                    'title'            => 'We launched the new editorial portal',
                    'slug'             => 'new-editorial-portal',
                    'excerpt'          => 'We released a refreshed editorial experience with clearer reading flow and navigation.',
                    'meta_title'       => 'New editorial portal | News',
                    'meta_description' => 'Discover the new editorial portal and its reading improvements.',
                    'rich_text'        => '<p>The editorial portal was refreshed to provide clearer navigation, image-backed cards, and a more consistent visual hierarchy.</p><p>The new layout improves readability on large and small screens without losing content context.</p>',
                ],
            ],
            [
                'sort_order'         => 2,
                'featured_image_url' => 'https://picsum.photos/id/1015/1200/800',
                'es' => [
                    'title'            => 'La colección de noticias ahora destaca portadas',
                    'slug'             => 'noticias-destacan-portadas',
                    'excerpt'          => 'Cada tarjeta del listado público puede mostrar una portada destacada si la entrada la tiene configurada.',
                    'meta_title'       => 'Noticias con portada | Noticias',
                    'meta_description' => 'Las tarjetas del listado ahora muestran portadas destacadas cuando existen.',
                    'rich_text'        => '<p>Las noticias del starter ahora incluyen imágenes de portada reales para que el grid de inicio no se vea vacío o incompleto.</p><p>Si una entrada no tiene imagen, la tarjeta sigue funcionando sin romper el diseño.</p>',
                ],
                'en' => [
                    'title'            => 'News now highlights cover images',
                    'slug'             => 'news-highlights-cover-images',
                    'excerpt'          => 'Each public listing card can show a featured cover when the entry has one configured.',
                    'meta_title'       => 'News with cover image | News',
                    'meta_description' => 'Listing cards now show featured cover images when available.',
                    'rich_text'        => '<p>The starter news items now include real cover images so the home grid no longer feels empty or incomplete.</p><p>If an entry has no image, the card still renders safely without breaking the layout.</p>',
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
                            'image_url' => $entry['featured_image_url'],
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
