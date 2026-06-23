<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seeds the "noticias" collection with categories, tags, and sample entries.
 * Idempotent: skips if the collection already exists.
 */
class NewsCollectionSeeder extends Seeder
{
    public function run(): void
    {
        // Guard: skip if already seeded
        $existing = $this->db->table('cms_collections')
            ->where('collection_key', 'noticias')
            ->get()
            ->getRowArray();

        if ($existing !== null) {
            echo "NewsCollectionSeeder: 'noticias' collection already exists, skipping.\n";
            return;
        }

        $langIds = $this->langIds(['es', 'en']);

        if (empty($langIds['es'])) {
            echo "NewsCollectionSeeder: 'es' language not found in cms_languages. Run CmsLanguageSeeder first.\n";
            return;
        }

        $this->db->transStart();

        // ── 1. Collection ──────────────────────────────────────────────────────
        $this->db->table('cms_collections')->insert([
            'collection_key'           => 'noticias',
            'url_prefix'               => '/noticias',
            'is_active'                => 1,
            'requires_approval'        => 0,
            'enables_categories'       => 1,
            'enables_tags'             => 1,
            'default_sitemap_priority' => '0.70',
            'default_changefreq'       => 'weekly',
            'sort_order'               => 10,
            'created_at'               => date('Y-m-d H:i:s'),
            'updated_at'               => date('Y-m-d H:i:s'),
        ]);
        $collectionId = (int) $this->db->insertID();

        // ── 2. Collection translations ─────────────────────────────────────────
        $collectionTranslations = [
            'es' => [
                'slug'                     => 'noticias',
                'name'                     => 'Noticias',
                'description'              => 'Sección de noticias y actualidad.',
                'listing_title'            => 'Últimas Noticias',
                'listing_intro'            => 'Mantente al día con todo lo que sucede.',
                'default_meta_title'       => 'Noticias | ' . getenv('app.name') ?: 'Noticias',
                'default_meta_description' => 'Lee las últimas noticias y actualizaciones.',
            ],
            'en' => [
                'slug'                     => 'news',
                'name'                     => 'News',
                'description'              => 'News and current events section.',
                'listing_title'            => 'Latest News',
                'listing_intro'            => 'Stay up to date with everything happening.',
                'default_meta_title'       => 'News | ' . getenv('app.name') ?: 'News',
                'default_meta_description' => 'Read the latest news and updates.',
            ],
        ];

        foreach ($collectionTranslations as $code => $trans) {
            $langId = $langIds[$code] ?? null;
            if ($langId === null) {
                continue;
            }
            $this->db->table('cms_collection_translations')->insert(array_merge([
                'collection_id' => $collectionId,
                'language_id'   => $langId,
            ], $trans));
        }

        // ── 3. Categories ──────────────────────────────────────────────────────
        $categoryDefs = [
            ['es' => ['name' => 'General',     'slug' => 'general'],     'en' => ['name' => 'General',        'slug' => 'general']],
            ['es' => ['name' => 'Actualidad',  'slug' => 'actualidad'],  'en' => ['name' => 'Current Events', 'slug' => 'current-events']],
            ['es' => ['name' => 'Eventos',     'slug' => 'eventos'],     'en' => ['name' => 'Events',         'slug' => 'events']],
            ['es' => ['name' => 'Tecnología',  'slug' => 'tecnologia'],  'en' => ['name' => 'Technology',     'slug' => 'technology']],
        ];

        $categoryIds = [];
        foreach ($categoryDefs as $i => $def) {
            $this->db->table('cms_categories')->insert([
                'collection_id' => $collectionId,
                'parent_id'     => null,
                'sort_order'    => $i + 1,
                'is_active'     => 1,
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ]);
            $catId = (int) $this->db->insertID();
            $categoryIds[$i] = $catId;

            foreach (['es', 'en'] as $code) {
                $langId = $langIds[$code] ?? null;
                if ($langId === null || !isset($def[$code])) {
                    continue;
                }
                $this->db->table('cms_category_translations')->insert([
                    'category_id' => $catId,
                    'language_id' => $langId,
                    'slug'        => $def[$code]['slug'],
                    'name'        => $def[$code]['name'],
                    'description' => null,
                    'meta_title'  => null,
                    'meta_description' => null,
                ]);
            }
        }

        // ── 4. Tags ────────────────────────────────────────────────────────────
        $tagDefs = [
            ['es' => 'destacado', 'en' => 'featured'],
            ['es' => 'nuevo',     'en' => 'new'],
        ];

        $tagIds = [];
        foreach ($tagDefs as $def) {
            $this->db->table('cms_tags')->insert([
                'is_active'  => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $tagId   = (int) $this->db->insertID();
            $tagIds[] = $tagId;

            foreach (['es', 'en'] as $code) {
                $langId = $langIds[$code] ?? null;
                if ($langId === null) {
                    continue;
                }
                $this->db->table('cms_tag_translations')->insert([
                    'tag_id'      => $tagId,
                    'language_id' => $langId,
                    'slug'        => $def[$code],
                    'name'        => ucfirst($def[$code]),
                ]);
            }
        }

        // ── 5. Sample entries ──────────────────────────────────────────────────
        $now = date('Y-m-d H:i:s');

        // Entry 1: Bienvenida (featured)
        $this->db->table('cms_entries')->insert([
            'collection_id'    => $collectionId,
            'author_id'        => null,
            'workflow_status'  => 'published',
            'published_at'     => $now,
            'scheduled_at'     => null,
            'is_featured'      => 1,
            'view_count'       => 0,
            'sort_order'       => 1,
            'sitemap_priority' => '0.80',
            'sitemap_changefreq' => 'weekly',
            'is_in_sitemap'    => 1,
            'created_at'       => $now,
            'updated_at'       => $now,
        ]);
        $entry1Id = (int) $this->db->insertID();

        $entry1Trans = [
            'es' => [
                'slug'             => 'bienvenidos-a-nuestras-noticias',
                'title'            => 'Bienvenidos a nuestras noticias',
                'excerpt'          => 'Hoy inauguramos nuestra sección de noticias. Aquí encontrarás información actualizada, eventos próximos y todo lo que necesitas saber.',
                'meta_title'       => 'Bienvenidos a nuestras noticias',
                'meta_description' => 'Inauguramos la sección de noticias con información actualizada y eventos próximos.',
            ],
            'en' => [
                'slug'             => 'welcome-to-our-news',
                'title'            => 'Welcome to Our News',
                'excerpt'          => 'Today we launch our news section. Here you will find updated information, upcoming events, and everything you need to know.',
                'meta_title'       => 'Welcome to Our News',
                'meta_description' => 'We launch our news section with updated information and upcoming events.',
            ],
        ];

        foreach ($entry1Trans as $code => $trans) {
            $langId = $langIds[$code] ?? null;
            if ($langId === null) {
                continue;
            }
            $this->db->table('cms_entry_translations')->insert(array_merge([
                'entry_id'    => $entry1Id,
                'language_id' => $langId,
                'featured_file_id' => null,
                'og_image_file_id' => null,
                'og_type'    => 'article',
                'canonical_url' => null,
                'robots'     => 'index, follow',
                'schema_data' => null,
            ], $trans));
        }

        // Entry 1 category: General (index 0)
        $this->db->table('cms_entry_categories')->insert([
            'entry_id'    => $entry1Id,
            'category_id' => $categoryIds[0],
            'sort_order'  => 1,
        ]);

        // Entry 1 tag: nuevo (index 1)
        $this->db->table('cms_entry_tags')->insert([
            'entry_id' => $entry1Id,
            'tag_id'   => $tagIds[1],
        ]);

        // Entry 1 rich_text block
        $this->insertRichTextBlock($entry1Id, $langIds, [
            'es' => '<p>Estamos muy emocionados de inaugurar esta sección de noticias. Desde aquí compartiremos novedades, eventos, actualizaciones y todo lo relevante para nuestra comunidad.</p><p>Te invitamos a visitarnos regularmente para estar al tanto de todo lo que sucede.</p>',
            'en' => '<p>We are very excited to launch this news section. From here we will share updates, events, news, and everything relevant to our community.</p><p>We invite you to visit us regularly to stay up to date with everything happening.</p>',
        ]);

        // Entry 2: Novedades (no featured)
        $this->db->table('cms_entries')->insert([
            'collection_id'    => $collectionId,
            'author_id'        => null,
            'workflow_status'  => 'published',
            'published_at'     => date('Y-m-d H:i:s', strtotime('-3 days')),
            'scheduled_at'     => null,
            'is_featured'      => 0,
            'view_count'       => 0,
            'sort_order'       => 2,
            'sitemap_priority' => '0.70',
            'sitemap_changefreq' => 'weekly',
            'is_in_sitemap'    => 1,
            'created_at'       => date('Y-m-d H:i:s', strtotime('-3 days')),
            'updated_at'       => date('Y-m-d H:i:s', strtotime('-3 days')),
        ]);
        $entry2Id = (int) $this->db->insertID();

        $entry2Trans = [
            'es' => [
                'slug'             => 'novedades-de-la-temporada',
                'title'            => 'Novedades de la temporada',
                'excerpt'          => 'Descubre todo lo que tenemos preparado para esta temporada: nuevas actividades, exposiciones especiales y mucho más.',
                'meta_title'       => 'Novedades de la temporada',
                'meta_description' => 'Nuevas actividades, exposiciones y eventos para esta temporada.',
            ],
            'en' => [
                'slug'             => 'season-updates',
                'title'            => 'Season Updates',
                'excerpt'          => 'Discover everything we have prepared for this season: new activities, special exhibitions, and much more.',
                'meta_title'       => 'Season Updates',
                'meta_description' => 'New activities, exhibitions, and events for this season.',
            ],
        ];

        foreach ($entry2Trans as $code => $trans) {
            $langId = $langIds[$code] ?? null;
            if ($langId === null) {
                continue;
            }
            $this->db->table('cms_entry_translations')->insert(array_merge([
                'entry_id'    => $entry2Id,
                'language_id' => $langId,
                'featured_file_id' => null,
                'og_image_file_id' => null,
                'og_type'    => 'article',
                'canonical_url' => null,
                'robots'     => 'index, follow',
                'schema_data' => null,
            ], $trans));
        }

        // Entry 2 category: Actualidad (index 1)
        $this->db->table('cms_entry_categories')->insert([
            'entry_id'    => $entry2Id,
            'category_id' => $categoryIds[1],
            'sort_order'  => 1,
        ]);

        // Entry 2 tag: destacado (index 0)
        $this->db->table('cms_entry_tags')->insert([
            'entry_id' => $entry2Id,
            'tag_id'   => $tagIds[0],
        ]);

        // Entry 2 rich_text block
        $this->insertRichTextBlock($entry2Id, $langIds, [
            'es' => '<p>Esta temporada viene cargada de sorpresas. Hemos preparado un programa completo con actividades para toda la familia, exposiciones temáticas y talleres especializados.</p><p>Consulta el calendario completo en nuestra web y reserva tu lugar con anticipación.</p>',
            'en' => '<p>This season is full of surprises. We have prepared a complete program with activities for the whole family, thematic exhibitions, and specialized workshops.</p><p>Check the full calendar on our website and reserve your spot in advance.</p>',
        ]);

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            echo "NewsCollectionSeeder: Transaction failed!\n";
        } else {
            echo "NewsCollectionSeeder: 'noticias' collection seeded successfully (collection_id={$collectionId}, entries={$entry1Id},{$entry2Id}).\n";
        }
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

    /**
     * Insert a rich_text block instance for an entry with per-language content.
     *
     * @param array<string, int> $langIds
     * @param array<string, string> $content  language_code => HTML
     */
    private function insertRichTextBlock(int $entryId, array $langIds, array $content): void
    {
        $blockRow = $this->db->table('cms_content_blocks')
            ->where('block_key', 'rich_text')
            ->get()
            ->getRowArray();

        if ($blockRow === null) {
            return;
        }

        $blockId = (int) $blockRow['id'];

        $this->db->table('cms_block_instances')->insert([
            'block_id'           => $blockId,
            'owner_type'         => 'entry',
            'owner_id'           => $entryId,
            'parent_instance_id' => null,
            'sort_order'         => 1,
            'column_index'       => null,
            'is_active'          => 1,
            'block_config'       => null,
            'created_at'         => date('Y-m-d H:i:s'),
            'updated_at'         => date('Y-m-d H:i:s'),
        ]);
        $instanceId = (int) $this->db->insertID();

        foreach ($content as $code => $html) {
            $langId = $langIds[$code] ?? null;
            if ($langId === null) {
                continue;
            }
            $this->db->table('cms_block_instance_translations')->insert([
                'instance_id' => $instanceId,
                'language_id' => $langId,
                'block_data'  => json_encode(['content' => $html]),
                'is_published' => 1,
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ]);
        }
    }
}
