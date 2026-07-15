<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class FixCollectionIndexPages extends Seeder
{
    public function run(): void
    {
        $db = \Config\Database::connect();
        $now = date('Y-m-d H:i:s');

        // Get language IDs
        $langIds = [];
        $langRows = $db->table('cms_languages')->get()->getResultArray();
        foreach ($langRows as $lang) {
            $langIds[$lang['code']] = (int) $lang['id'];
        }

        // Fix portfolio collection index page
        $portfolio = $db->table('cms_collections')
            ->where('collection_key', 'portafolio')
            ->get()->getRow();

        if ($portfolio) {
            // Check if index page exists
            $indexPage = $db->table('cms_pages')
                ->where('page_type', 'collection_index')
                ->where('collection_id', $portfolio->id)
                ->where('deleted_at IS NULL', null, false)
                ->get()->getRow();

            if (!$indexPage) {
                // Create index page
                $pageId = $db->table('cms_pages')->insert([
                    'page_type' => 'collection_index',
                    'collection_id' => $portfolio->id,
                    'status' => 'published',
                    'published_at' => $now,
                    'sort_order' => 1,
                    'sitemap_priority' => '0.8',
                    'is_in_sitemap' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $pageId = $db->insertID();

                // Add translations
                $db->table('cms_page_translations')->insert([
                    'page_id' => $pageId,
                    'language_id' => $langIds['es'],
                    'slug' => 'portafolio',
                    'title' => 'Portafolio',
                    'excerpt' => 'Nuestros proyectos y casos de éxito.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $db->table('cms_page_translations')->insert([
                    'page_id' => $pageId,
                    'language_id' => $langIds['en'],
                    'slug' => 'portfolio',
                    'title' => 'Portfolio',
                    'excerpt' => 'Our projects and success stories.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                echo "✓ Portfolio index page created\n";
            } else {
                echo "✓ Portfolio index page already exists\n";
            }
        }

        // Fix news collection index page
        $news = $db->table('cms_collections')
            ->where('collection_key', 'noticias')
            ->get()->getRow();

        if ($news) {
            $indexPage = $db->table('cms_pages')
                ->where('page_type', 'collection_index')
                ->where('collection_id', $news->id)
                ->where('deleted_at IS NULL', null, false)
                ->get()->getRow();

            if (!$indexPage) {
                $pageId = $db->table('cms_pages')->insert([
                    'page_type' => 'collection_index',
                    'collection_id' => $news->id,
                    'status' => 'published',
                    'published_at' => $now,
                    'sort_order' => 1,
                    'sitemap_priority' => '0.8',
                    'is_in_sitemap' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $pageId = $db->insertID();

                $db->table('cms_page_translations')->insert([
                    'page_id' => $pageId,
                    'language_id' => $langIds['es'],
                    'slug' => 'noticias',
                    'title' => 'Noticias',
                    'excerpt' => 'Mantente actualizado con nuestras últimas noticias.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $db->table('cms_page_translations')->insert([
                    'page_id' => $pageId,
                    'language_id' => $langIds['en'],
                    'slug' => 'news',
                    'title' => 'News',
                    'excerpt' => 'Stay updated with our latest news.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                echo "✓ News index page created\n";
            } else {
                echo "✓ News index page already exists\n";
            }
        }
    }
}
