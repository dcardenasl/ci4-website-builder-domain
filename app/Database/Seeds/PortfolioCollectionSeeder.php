<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use App\Database\Seeds\Concerns\CollectionBlockPresets;
use App\Database\Seeds\Concerns\IdempotentSeederSupport;
use CodeIgniter\Database\Seeder;

/**
 * Seeds the starter site's portfolio collection with categories, tags, and sample entries.
 * Idempotent across repeated bootstrap runs and partial reseeds.
 */
class PortfolioCollectionSeeder extends Seeder
{
    use IdempotentSeederSupport;

    public function run(): void
    {
        $langIds = $this->langIds(['es', 'en']);

        if (empty($langIds['es'])) {
            echo "PortfolioCollectionSeeder: 'es' language not found in cms_languages. Seed CmsLanguageSeeder first.\n";
            return;
        }

        // $this->db->transStart();
        $preset = CollectionBlockPresets::portfolio();

        // ── 1. Collection ──────────────────────────────────────────────────────
        $collectionId = $this->upsertRecord('cms_collections', [
            'collection_key' => 'portafolio',
        ], [
            'collection_key'           => 'portafolio',
            'collection_type'          => 'portfolio',
            'is_active'                => 1,
            'requires_approval'        => 0,
            'enables_categories'       => 1,
            'enables_tags'             => 1,
            'default_sitemap_priority' => '0.80',
            'default_changefreq'       => 'monthly',
            'sort_order'               => 20,
            'block_template'           => json_encode($preset['block_template'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'wizard_config'            => json_encode($preset['wizard_config'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        if ($collectionId === null) {
            echo "PortfolioCollectionSeeder: unable to seed 'portafolio' collection.\n";
            return;
        }

        // ── 2. Collection translations ─────────────────────────────────────────
        $collectionTranslations = [
            'es' => [
                'slug'                     => 'portafolio',
                'name'                     => 'Portafolio',
                'description'              => 'Sección de casos de éxito y portafolio de proyectos.',
                'listing_title'            => 'Nuestros Proyectos',
                'listing_intro'            => 'Explora nuestros trabajos recientes y casos de éxito.',
                'default_meta_title'       => 'Portafolio | Mi Sitio',
                'default_meta_description' => 'Conoce los proyectos y desarrollos que hemos realizado.',
            ],
            'en' => [
                'slug'                     => 'portfolio',
                'name'                     => 'Portfolio',
                'description'              => 'Portfolio and success stories section.',
                'listing_title'            => 'Our Projects',
                'listing_intro'            => 'Explore our recent works and success stories.',
                'default_meta_title'       => 'Portfolio | My Site',
                'default_meta_description' => 'Explore the projects and works we have completed.',
            ],
        ];

        foreach ($collectionTranslations as $langCode => $trans) {
            $langId = $langIds[$langCode] ?? null;
            if ($langId === null) {
                continue;
            }
            $this->upsertRecord('cms_collection_translations', [
                'collection_id' => $collectionId,
                'language_id'   => $langId,
            ], $trans);
        }

        // ── 3. Categories ──────────────────────────────────────────────────────
        $categories = [
            ['es' => ['name' => 'Desarrollo Web', 'slug' => 'desarrollo-web'], 'en' => ['name' => 'Web Development', 'slug' => 'web-development']],
            ['es' => ['name' => 'Diseño UI/UX',    'slug' => 'diseno-ui-ux'],    'en' => ['name' => 'UI/UX Design',      'slug' => 'ui-ux-design']],
        ];

        $catIdMap = [];
        foreach ($categories as $index => $cat) {
            $catId = $this->upsertRecord('cms_categories', [
                'collection_id' => $collectionId,
                'sort_order'    => $index + 1,
            ], [
                'parent_id'  => null,
                'is_active'   => 1,
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

        // ── 4. Tags ────────────────────────────────────────────────────────────
        $tags = [
            ['es' => ['name' => 'Reciente', 'slug' => 'reciente'], 'en' => ['name' => 'Recent', 'slug' => 'recent']],
            ['es' => ['name' => 'Destacado', 'slug' => 'destacado'], 'en' => ['name' => 'Featured', 'slug' => 'featured']],
        ];

        $tagIdMap = [];
        foreach ($tags as $tag) {
            $tagId = $this->upsertTag($tag, $langIds);
            if ($tagId <= 0) {
                continue;
            }

            $tagIdMap[$tag['es']['slug']] = $tagId;
        }

        // ── 5. Entries (Sample Projects) ───────────────────────────────────────
        $entries = [
            [
                'featured_image'     => $this->mediaReference('https://picsum.photos/id/2/600/400'),
                'detail_image'       => $this->mediaReference('https://picsum.photos/id/1040/1200/800'),
                'category_slug'      => 'desarrollo-web',
                'tag_slugs'          => ['reciente', 'destacado'],
                'es' => [
                    'title'            => 'Plataforma E-commerce Nacional',
                    'slug'             => 'ecommerce-nacional',
                    'excerpt'          => 'Desarrollo a gran escala de una tienda online moderna con pasarela de pago integrada.',
                    'meta_title'       => 'Plataforma E-commerce | Portafolio',
                    'meta_description' => 'Caso de éxito sobre el desarrollo de una tienda online moderna y escalable.',
                    'rich_text'        => '<p>Diseñamos y desarrollamos una solución de comercio electrónico completa que permite transacciones rápidas, administración sencilla de inventarios y una interfaz móvil sumamente intuitiva.</p><h2>Del descubrimiento al lanzamiento</h2><p>El equipo comenzó mapeando los momentos de mayor fricción: búsqueda, checkout y seguimiento del pedido. Con esa información definimos una arquitectura modular y un sistema visual capaz de crecer junto al catálogo.</p><ul><li>Catálogo y filtros preparados para miles de productos.</li><li>Checkout optimizado para pantallas pequeñas.</li><li>Panel editorial para actualizar campañas sin depender de desarrollo.</li></ul><p>El proyecto logró un aumento del 40% en conversiones móviles en su primer trimestre.</p>',
                ],
                'en' => [
                    'title'            => 'National E-commerce Platform',
                    'slug'             => 'national-ecommerce',
                    'excerpt'          => 'Large-scale development of a modern online store with integrated payment gateway.',
                    'meta_title'       => 'E-commerce Platform | Portfolio',
                    'meta_description' => 'Success story on the development of a modern and scalable online store.',
                    'rich_text'        => '<p>We designed and developed a complete e-commerce solution enabling fast transactions, easy inventory management, and a highly intuitive mobile interface.</p><h2>From discovery to launch</h2><p>The team mapped the highest-friction moments: search, checkout, and order tracking. We used those insights to define a modular architecture and a visual system that could grow with the catalog.</p><ul><li>Catalog and filters ready for thousands of products.</li><li>A checkout flow optimized for small screens.</li><li>An editorial panel for campaign updates without developer support.</li></ul><p>The project delivered a 40% increase in mobile conversion during its first quarter.</p>',
                ],
            ],
            [
                'featured_image'     => $this->mediaReference('https://picsum.photos/id/10/600/400'),
                'detail_image'       => $this->mediaReference('https://picsum.photos/id/1050/1200/800'),
                'category_slug'      => 'diseno-ui-ux',
                'tag_slugs'          => ['destacado'],
                'es' => [
                    'title'            => 'Rediseño App de Banca Digital',
                    'slug'             => 'rediseno-banca-digital',
                    'excerpt'          => 'Nueva propuesta de interfaz y experiencia de usuario enfocada en la simplicidad y accesibilidad.',
                    'meta_title'       => 'Rediseño UI/UX Banca | Portafolio',
                    'meta_description' => 'Proyecto de diseño UX/UI para transformar la experiencia de banca móvil digital.',
                    'rich_text'        => '<p>Llevamos a cabo talleres de investigación y diseño centrado en el usuario para simplificar el flujo de transferencias bancarias de 5 a sólo 2 pasos, logrando un diseño limpio y moderno.</p><h2>Menos pasos, más confianza</h2><p>La investigación reveló que las personas no necesitaban más funciones, sino mejor orientación en los momentos críticos. Convertimos esas señales en prototipos, pruebas moderadas y una interfaz accesible.</p><ul><li>Flujo principal reducido de cinco pasos a dos.</li><li>Estados y confirmaciones más fáciles de entender.</li><li>Componentes accesibles y consistentes en toda la aplicación.</li></ul><p>El resultado fue una experiencia más clara para usuarios nuevos y más rápida para quienes realizan operaciones todos los días.</p>',
                ],
                'en' => [
                    'title'            => 'Digital Banking App Redesign',
                    'slug'             => 'digital-banking-redesign',
                    'excerpt'          => 'New interface and user experience design focused on simplicity and accessibility.',
                    'meta_title'       => 'Banking UI/UX Redesign | Portfolio',
                    'meta_description' => 'UI/UX design project to transform the digital mobile banking experience.',
                    'rich_text'        => '<p>We conducted user-centered research and design workshops to simplify bank transfers from 5 to just 2 steps, achieving a clean and modern design.</p><h2>Fewer steps, more confidence</h2><p>Research showed that people did not need more features; they needed better guidance at critical moments. We turned those signals into prototypes, moderated tests, and an accessible interface.</p><ul><li>The primary flow was reduced from five steps to two.</li><li>Statuses and confirmations became easier to understand.</li><li>Accessible, consistent components were used across the app.</li></ul><p>The result was a clearer experience for new users and a faster one for people completing daily transactions.</p>',
                ],
            ],
        ];

        $blockIds = $this->blockIds(['rich_text', 'image']);
        $keptInstanceIds = [];

        foreach ($entries as $index => $entry) {
            $entryId = $this->upsertRecord('cms_entries', [
                'collection_id' => $collectionId,
                'sort_order'    => ($index + 1) * 10,
            ], [
                'workflow_status' => 'published',
                'is_featured'     => in_array('destacado', $entry['tag_slugs'], true) ? 1 : 0,
                'published_at'    => date('Y-m-d H:i:s'),
            ]);

            if ($entryId === null) {
                continue;
            }

            // Category relation
            $catSlug = $entry['category_slug'];
            $catId   = $catIdMap[$catSlug] ?? null;
            if ($catId !== null) {
                $this->upsertRecord('cms_entry_categories', [
                    'entry_id'    => $entryId,
                    'category_id' => $catId,
                ], []);
            }

            // Tag relations
            foreach ($entry['tag_slugs'] as $tagSlug) {
                $tagId = $tagIdMap[$tagSlug] ?? null;
                if ($tagId !== null) {
                    $this->upsertRecord('cms_entry_tags', [
                        'entry_id' => $entryId,
                        'tag_id'   => $tagId,
                    ], []);
                }
            }

            // Create single block instances for the entry (shared across languages)
            // Block 1: image
            $instImageId = $this->upsertRecord('cms_block_instances', [
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

            // Block 2: rich_text
            $instRichTextId = $this->upsertRecord('cms_block_instances', [
                'block_id'   => $blockIds['rich_text'],
                'owner_type' => 'entry',
                'owner_id'   => $entryId,
                'sort_order' => 2,
            ], [
                'is_active' => 1,
            ]);
            $keptInstanceIds = array_values(array_filter([$instImageId, $instRichTextId]));

            // Translations
            foreach (['es', 'en'] as $langCode) {
                $langId = $langIds[$langCode] ?? null;
                if ($langId === null) {
                    continue;
                }
                $tData = $entry[$langCode];
                $featuredImageColumns = $this->mediaReferenceColumns(
                    $entry['featured_image'] ?? null,
                    'featured_file_id',
                    'featured_image_url'
                );

                $this->upsertRecord('cms_entry_translations', [
                    'entry_id'         => $entryId,
                    'language_id'      => $langId,
                ], [
                    'title'            => $tData['title'],
                    'slug'             => $tData['slug'],
                    'excerpt'          => $tData['excerpt'],
                    'featured_file_id' => $featuredImageColumns['featured_file_id'],
                    'featured_image_url' => $featuredImageColumns['featured_image_url'],
                    'meta_title'       => $tData['meta_title'],
                    'meta_description' => $tData['meta_description'],
                ]);

                // Insert translation for Block 1: image
                $this->upsertRecord('cms_block_instance_translations', [
                    'instance_id' => $instImageId,
                    'language_id' => $langId,
                ], [
                    'block_data'  => json_encode([
                        'alt'     => $tData['title'],
                        'caption' => 'Proyecto finalizado: ' . $tData['title']
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]);

                // Insert translation for Block 2: rich_text
                $this->upsertRecord('cms_block_instance_translations', [
                    'instance_id' => $instRichTextId,
                    'language_id' => $langId,
                ], [
                    'block_data'  => json_encode(['content' => $tData['rich_text']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]);
            }

            // Case study entries are article content: a cover image and body
            // text. The article template already renders the breadcrumb,
            // title, category badges, date, and back-link, so entries do not
            // need their own page_header/hero_banner/cta blocks — those are
            // landing-page building blocks and would duplicate what the
            // template already shows.
            $this->cleanupStaleEntryBlocks($entryId, $keptInstanceIds);
        }
        // $this->db->transComplete();
        echo "PortfolioCollectionSeeder: 'portafolio' collection and 2 sample entries seeded.\n";
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
     * @param array<string, array{name: string, slug: string}> $tag
     * @param array<string, int>                               $langIds
     */
    private function upsertTag(array $tag, array $langIds): int
    {
        $slugs = [];
        foreach ($tag as $translation) {
            $slugs[] = $translation['slug'];
        }

        $existingTag = $this->db->table('cms_tags')
            ->select('cms_tags.id')
            ->join('cms_tag_translations', 'cms_tag_translations.tag_id = cms_tags.id')
            ->whereIn('cms_tag_translations.slug', $slugs)
            ->groupBy('cms_tags.id')
            ->orderBy('cms_tags.id', 'ASC')
            ->limit(1)
            ->get()
            ->getRowArray();

        if ($existingTag !== null) {
            $tagId = (int) $existingTag['id'];
        } else {
            $tagId = $this->createRecord('cms_tags', [
                'is_active' => 1,
            ]) ?? 0;
        }

        if ($tagId <= 0) {
            return 0;
        }

        foreach ($tag as $langCode => $trans) {
            $langId = $langIds[$langCode] ?? null;
            if ($langId === null) {
                continue;
            }

            $this->upsertRecord('cms_tag_translations', [
                'tag_id'      => $tagId,
                'language_id' => $langId,
            ], [
                'name' => $trans['name'],
                'slug' => $trans['slug'],
            ]);
        }

        return $tagId;
    }
}
