<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSlugToCmsCollectionTranslations extends Migration
{
    public function up(): void
    {
        $db = $this->db;

        $db->query("ALTER TABLE `cms_collection_translations`
            ADD COLUMN `slug` VARCHAR(150) DEFAULT NULL AFTER `language_id`,
            ADD UNIQUE KEY `uk_collection_slug_lang` (`language_id`, `slug`)");

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

    public function down(): void
    {
        $db = $this->db;

        $db->query("ALTER TABLE `cms_collection_translations`
            DROP INDEX `uk_collection_slug_lang`,
            DROP COLUMN `slug`");
    }
}
