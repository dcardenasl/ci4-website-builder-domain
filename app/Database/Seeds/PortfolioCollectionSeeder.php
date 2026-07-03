<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seeds the starter site's portfolio collection with categories, tags, and sample entries.
 * Idempotent: skips if the collection already exists.
 */
class PortfolioCollectionSeeder extends Seeder
{
    public function run(): void
    {
        $existing = $this->db->table('cms_collections')
            ->where('collection_key', 'portafolio')
            ->get()
            ->getRowArray();

        if ($existing !== null) {
            echo "PortfolioCollectionSeeder: 'portafolio' collection already exists, skipping.\n";
            return;
        }

        $langIds = $this->langIds(['es', 'en']);

        if (empty($langIds['es'])) {
            echo "PortfolioCollectionSeeder: 'es' language not found in cms_languages. Seed CmsLanguageSeeder first.\n";
            return;
        }

        // $this->db->transStart();
        $preset = [
            'block_template' => [
                'version' => '1.0',
                'blocks' => [
                    [
                        'block_key' => 'rich_text',
                        'label' => 'Detalle del Proyecto',
                        'help_text' => 'Descripción detallada del caso de estudio',
                        'required' => true,
                        'locked' => true,
                        'block_config_defaults' => new \stdClass(),
                        'sort_order' => 1,
                    ],
                    [
                        'block_key' => 'image',
                        'label' => 'Imagen del Proyecto',
                        'help_text' => 'Imagen principal del proyecto realizado',
                        'required' => false,
                        'locked' => false,
                        'block_config_defaults' => new \stdClass(),
                        'sort_order' => 2,
                    ],
                ],
            ],
            'wizard_config' => [
                'type' => 'portfolio',
                'steps' => [
                    ['step_title' => 'Proyecto', 'step_hint' => 'Nombre o título del proyecto', 'fields' => [['key' => 'title', 'label' => 'Proyecto', 'type' => 'text', 'required' => true]]],
                    ['step_title' => 'Resumen', 'step_hint' => 'Una breve descripción del trabajo realizado', 'fields' => [['key' => 'excerpt', 'label' => 'Resumen', 'type' => 'textarea', 'required' => false]]],
                ],
            ],
        ];

        // ── 1. Collection ──────────────────────────────────────────────────────
        $this->db->table('cms_collections')->insert([
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
            'created_at'               => date('Y-m-d H:i:s'),
            'updated_at'               => date('Y-m-d H:i:s'),
        ]);
        $collectionId = (int) $this->db->insertID();

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
            $this->db->table('cms_collection_translations')->insert(array_merge([
                'collection_id' => $collectionId,
                'language_id'   => $langId,
            ], $trans));
        }

        // ── 3. Categories ──────────────────────────────────────────────────────
        $categories = [
            ['es' => ['name' => 'Desarrollo Web', 'slug' => 'desarrollo-web'], 'en' => ['name' => 'Web Development', 'slug' => 'web-development']],
            ['es' => ['name' => 'Diseño UI/UX',    'slug' => 'diseno-ui-ux'],    'en' => ['name' => 'UI/UX Design',      'slug' => 'ui-ux-design']],
        ];

        $catIdMap = [];
        foreach ($categories as $index => $cat) {
            $this->db->table('cms_categories')->insert([
                'collection_id' => $collectionId,
                'parent_id'     => null,
                'sort_order'    => $index + 1,
                'is_active'     => 1,
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ]);
            $catId = (int) $this->db->insertID();

            foreach ($cat as $langCode => $trans) {
                $langId = $langIds[$langCode] ?? null;
                if ($langId === null) {
                    continue;
                }
                $this->db->table('cms_category_translations')->insert([
                    'category_id' => $catId,
                    'language_id' => $langId,
                    'name'        => $trans['name'],
                    'slug'        => $trans['slug'],
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
            $existingTag = $this->db->table('cms_tags')
                ->select('cms_tags.id')
                ->join('cms_tag_translations', 'cms_tag_translations.tag_id = cms_tags.id')
                ->where('cms_tag_translations.slug', $tag['es']['slug'])
                ->get()
                ->getRowArray();

            if ($existingTag !== null) {
                $tagIdMap[$tag['es']['slug']] = (int) $existingTag['id'];
                continue;
            }

            $this->db->table('cms_tags')->insert([
                'is_active'  => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $tagId = (int) $this->db->insertID();

            foreach ($tag as $langCode => $trans) {
                $langId = $langIds[$langCode] ?? null;
                if ($langId === null) {
                    continue;
                }
                $this->db->table('cms_tag_translations')->insert([
                    'tag_id'      => $tagId,
                    'language_id' => $langId,
                    'name'        => $trans['name'],
                    'slug'        => $trans['slug'],
                ]);
            }
            $tagIdMap[$tag['es']['slug']] = $tagId;
        }

        // ── 5. Entries (Sample Projects) ───────────────────────────────────────
        $entries = [
            [
                'featured_image_url' => 'https://images.unsplash.com/photo-1507238691740-187a5b1d37b8?auto=format&fit=crop&w=600&q=80',
                'category_slug'      => 'desarrollo-web',
                'tag_slugs'          => ['reciente', 'destacado'],
                'es' => [
                    'title'            => 'Plataforma E-commerce Nacional',
                    'slug'             => 'ecommerce-nacional',
                    'excerpt'          => 'Desarrollo a gran escala de una tienda online moderna con pasarela de pago integrada.',
                    'meta_title'       => 'Plataforma E-commerce | Portafolio',
                    'meta_description' => 'Caso de éxito sobre el desarrollo de una tienda online moderna y escalable.',
                    'rich_text'        => '<p>Diseñamos y desarrollamos una solución de comercio electrónico completa que permite transacciones rápidas, administración sencilla de inventarios y una interfaz móvil sumamente intuitiva.</p><p>El proyecto logró un aumento del 40% en conversiones móviles en su primer trimestre.</p>',
                ],
                'en' => [
                    'title'            => 'National E-commerce Platform',
                    'slug'             => 'national-ecommerce',
                    'excerpt'          => 'Large-scale development of a modern online store with integrated payment gateway.',
                    'meta_title'       => 'E-commerce Platform | Portfolio',
                    'meta_description' => 'Success story on the development of a modern and scalable online store.',
                    'rich_text'        => '<p>We designed and developed a complete e-commerce solution enabling fast transactions, easy inventory management, and a highly intuitive mobile interface.</p>',
                ],
            ],
            [
                'featured_image_url' => 'https://images.unsplash.com/photo-1586717791821-3f44a563fa4c?auto=format&fit=crop&w=600&q=80',
                'category_slug'      => 'diseno-ui-ux',
                'tag_slugs'          => ['destacado'],
                'es' => [
                    'title'            => 'Rediseño App de Banca Digital',
                    'slug'             => 'rediseno-banca-digital',
                    'excerpt'          => 'Nueva propuesta de interfaz y experiencia de usuario enfocada en la simplicidad y accesibilidad.',
                    'meta_title'       => 'Rediseño UI/UX Banca | Portafolio',
                    'meta_description' => 'Proyecto de diseño UX/UI para transformar la experiencia de banca móvil digital.',
                    'rich_text'        => '<p>Llevamos a cabo talleres de investigación y diseño centrado en el usuario para simplificar el flujo de transferencias bancarias de 5 a solo 2 pasos simples, logrando un diseño limpio y moderno.</p>',
                ],
                'en' => [
                    'title'            => 'Digital Banking App Redesign',
                    'slug'             => 'digital-banking-redesign',
                    'excerpt'          => 'New interface and user experience design focused on simplicity and accessibility.',
                    'meta_title'       => 'Banking UI/UX Redesign | Portfolio',
                    'meta_description' => 'UI/UX design project to transform the digital mobile banking experience.',
                    'rich_text'        => '<p>We conducted user-centered research and design workshops to simplify bank transfers from 5 to just 2 simple steps, achieving a clean and modern design.</p>',
                ],
            ],
        ];

        $blockIds = $this->blockIds(['rich_text', 'image']);

        foreach ($entries as $index => $entry) {
            $this->db->table('cms_entries')->insert([
                'collection_id'      => $collectionId,
                'workflow_status'    => 'published',
                'is_featured'        => in_array('destacado', $entry['tag_slugs'], true) ? 1 : 0,
                'sort_order'         => ($index + 1) * 10,
                'published_at'       => date('Y-m-d H:i:s'),
                'created_at'         => date('Y-m-d H:i:s'),
                'updated_at'         => date('Y-m-d H:i:s'),
            ]);
            $entryId = (int) $this->db->insertID();

            // Category relation
            $catSlug = $entry['category_slug'];
            $catId   = $catIdMap[$catSlug] ?? null;
            if ($catId !== null) {
                $this->db->table('cms_entry_categories')->insert([
                    'entry_id'    => $entryId,
                    'category_id' => $catId,
                ]);
            }

            // Tag relations
            foreach ($entry['tag_slugs'] as $tagSlug) {
                $tagId = $tagIdMap[$tagSlug] ?? null;
                if ($tagId !== null) {
                    $this->db->table('cms_entry_tags')->insert([
                        'entry_id' => $entryId,
                        'tag_id'   => $tagId,
                    ]);
                }
            }

            // Translations
            foreach (['es', 'en'] as $langCode) {
                $langId = $langIds[$langCode] ?? null;
                if ($langId === null) {
                    continue;
                }
                $tData = $entry[$langCode];

                $this->db->table('cms_entry_translations')->insert([
                    'entry_id'         => $entryId,
                    'language_id'      => $langId,
                    'title'            => $tData['title'],
                    'slug'             => $tData['slug'],
                    'excerpt'          => $tData['excerpt'],
                    'meta_title'       => $tData['meta_title'],
                    'meta_description' => $tData['meta_description'],
                ]);

                // Auto-create block instances based on template
                // Block 1: rich_text
                $this->db->table('cms_block_instances')->insert([
                    'block_id'   => $blockIds['rich_text'],
                    'owner_type' => 'entry',
                    'owner_id'   => $entryId,
                    'sort_order' => 1,
                    'is_active'  => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $inst1Id = (int) $this->db->insertID();

                $this->db->table('cms_block_instance_translations')->insert([
                    'instance_id' => $inst1Id,
                    'language_id' => $langId,
                    'block_data'  => json_encode(['content' => $tData['rich_text']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'created_at'  => date('Y-m-d H:i:s'),
                    'updated_at'  => date('Y-m-d H:i:s'),
                ]);

                // Block 2: image
                $this->db->table('cms_block_instances')->insert([
                    'block_id'   => $blockIds['image'],
                    'owner_type' => 'entry',
                    'owner_id'   => $entryId,
                    'sort_order' => 2,
                    'is_active'  => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $inst2Id = (int) $this->db->insertID();

                $this->db->table('cms_block_instance_translations')->insert([
                    'instance_id' => $inst2Id,
                    'language_id' => $langId,
                    'block_data'  => json_encode([
                        'image_url' => $entry['featured_image_url'],
                        'alt'       => $tData['title'],
                        'caption'   => 'Proyecto finalizado: ' . $tData['title']
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'created_at'  => date('Y-m-d H:i:s'),
                    'updated_at'  => date('Y-m-d H:i:s'),
                ]);
            }
        }
        // $this->db->transComplete();
        echo "PortfolioCollectionSeeder: 'portafolio' collection and 2 sample entries seeded.\n";
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
}
