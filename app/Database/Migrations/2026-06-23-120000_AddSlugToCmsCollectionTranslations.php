<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSlugToCmsCollectionTranslations extends Migration
{
    public function up(): void
    {
        $db = $this->db;

        $columnExists = $db->query(
            "SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS
             WHERE table_schema = DATABASE()
               AND table_name = 'cms_collection_translations'
               AND column_name = 'slug'"
        );

        if ($columnExists && (int) $columnExists->getRowArray()['cnt'] === 0) {
            $db->query("ALTER TABLE `cms_collection_translations`
                ADD COLUMN `slug` VARCHAR(150) DEFAULT NULL AFTER `language_id`");
        }

        $indexExists = $db->query(
            "SELECT COUNT(*) AS cnt FROM information_schema.STATISTICS
             WHERE table_schema = DATABASE()
               AND table_name = 'cms_collection_translations'
               AND index_name = 'uk_collection_slug_lang'"
        );

        if ($indexExists && (int) $indexExists->getRowArray()['cnt'] === 0) {
            $db->query("ALTER TABLE `cms_collection_translations`
                ADD UNIQUE KEY `uk_collection_slug_lang` (`language_id`, `slug`)");
        }

        if ($columnExists && (int) $columnExists->getRowArray()['cnt'] === 0) {
            $db->query("UPDATE `cms_collection_translations` AS cct
                INNER JOIN `cms_collections` AS c ON c.id = cct.collection_id
                INNER JOIN `cms_languages` AS l ON l.id = cct.language_id
                SET cct.slug = CASE
                    WHEN c.collection_key = 'noticias' AND l.code = 'en' THEN 'news'
                    WHEN c.collection_key = 'noticias' AND l.code = 'es' THEN 'noticias'
                    ELSE TRIM(BOTH '/' FROM c.url_prefix)
                END
                WHERE cct.slug IS NULL OR cct.slug = ''");
        }
    }

    public function down(): void
    {
        // Intentionally left as a no-op.
        // This migration is a one-way schema cleanup and the app no longer
        // depends on the old column shape.
    }
}
