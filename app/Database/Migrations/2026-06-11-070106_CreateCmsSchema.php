<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCmsSchema extends Migration
{
    public function up(): void
    {
        $db = $this->db;

        // Disable foreign keys temporarily to prevent order-of-creation issues
        $db->query('SET FOREIGN_KEY_CHECKS = 0');

        // 1. cms_languages
        $db->query("CREATE TABLE `cms_languages` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `code` VARCHAR(10) NOT NULL,
            `name` VARCHAR(50) NOT NULL,
            `native_name` VARCHAR(50) NOT NULL,
            `is_default` TINYINT(1) NOT NULL DEFAULT 0,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `fallback_language_id` INT UNSIGNED DEFAULT NULL,
            `sort_order` INT NOT NULL DEFAULT 0,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_lang_code` (`code`),
            KEY `idx_lang_active_sort` (`is_active`, `sort_order`),
            CONSTRAINT `fk_lang_fallback` FOREIGN KEY (`fallback_language_id`) REFERENCES `cms_languages` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 2. cms_settings
        $db->query("CREATE TABLE `cms_settings` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `setting_key` VARCHAR(100) NOT NULL,
            `setting_value` LONGTEXT,
            `setting_type` ENUM('string','int','bool','json','file_id') NOT NULL DEFAULT 'string',
            `setting_group` VARCHAR(50) NOT NULL DEFAULT 'general',
            `is_translatable` TINYINT(1) NOT NULL DEFAULT 0,
            `sort_order` INT NOT NULL DEFAULT 0,
            `description` VARCHAR(255) DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_setting_key` (`setting_key`),
            KEY `idx_setting_group` (`setting_group`, `sort_order`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 3. cms_setting_translations
        $db->query("CREATE TABLE `cms_setting_translations` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `setting_id` INT UNSIGNED NOT NULL,
            `language_id` INT UNSIGNED NOT NULL,
            `setting_value` LONGTEXT,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_setting_lang` (`setting_id`, `language_id`),
            KEY `idx_settrans_lang` (`language_id`),
            CONSTRAINT `fk_settrans_setting` FOREIGN KEY (`setting_id`) REFERENCES `cms_settings` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_settrans_lang` FOREIGN KEY (`language_id`) REFERENCES `cms_languages` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 4. cms_pages
        $db->query("CREATE TABLE `cms_pages` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `parent_id` INT UNSIGNED DEFAULT NULL,
            `page_type` ENUM('home','generic','contact','privacy','terms','404','500','maintenance') NOT NULL DEFAULT 'generic',
            `type_singleton` VARCHAR(20) GENERATED ALWAYS AS (
                CASE
                    WHEN `page_type` IN ('home','404','500','maintenance','contact','privacy','terms')
                        AND `deleted_at` IS NULL
                    THEN `page_type`
                    ELSE NULL
                END
            ) STORED,
            `status` ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
            `published_at` DATETIME DEFAULT NULL,
            `scheduled_at` DATETIME DEFAULT NULL,
            `sort_order` INT NOT NULL DEFAULT 0,
            `sitemap_priority` DECIMAL(2,1) DEFAULT NULL,
            `sitemap_changefreq` ENUM('always','hourly','daily','weekly','monthly','yearly','never') DEFAULT 'monthly',
            `is_in_sitemap` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            `deleted_at` DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_page_type_singleton` (`type_singleton`),
            KEY `idx_page_parent_sort` (`parent_id`, `sort_order`),
            KEY `idx_page_status` (`status`, `deleted_at`),
            CONSTRAINT `fk_page_parent` FOREIGN KEY (`parent_id`) REFERENCES `cms_pages` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 5. cms_page_translations
        $db->query("CREATE TABLE `cms_page_translations` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `page_id` INT UNSIGNED NOT NULL,
            `language_id` INT UNSIGNED NOT NULL,
            `slug` VARCHAR(150) NOT NULL,
            `title` VARCHAR(255) NOT NULL,
            `excerpt` VARCHAR(500) DEFAULT NULL,
            `meta_title` VARCHAR(255) DEFAULT NULL,
            `meta_description` VARCHAR(500) DEFAULT NULL,
            `og_image_file_id` INT UNSIGNED DEFAULT NULL,
            `og_type` VARCHAR(50) DEFAULT NULL,
            `canonical_url` VARCHAR(500) DEFAULT NULL,
            `robots` VARCHAR(100) DEFAULT NULL,
            `schema_data` JSON DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_page_lang` (`page_id`, `language_id`),
            UNIQUE KEY `uk_page_slug_lang` (`language_id`, `slug`),
            KEY `idx_pagetrans_lang` (`language_id`),
            FULLTEXT KEY `ft_page_search` (`title`, `excerpt`),
            CONSTRAINT `fk_pagetrans_page` FOREIGN KEY (`page_id`) REFERENCES `cms_pages` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_pagetrans_lang` FOREIGN KEY (`language_id`) REFERENCES `cms_languages` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 6. cms_page_versions
        $db->query("CREATE TABLE `cms_page_versions` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `page_id` INT UNSIGNED NOT NULL,
            `version_number` INT NOT NULL,
            `snapshot` JSON NOT NULL,
            `created_by` INT UNSIGNED DEFAULT NULL,
            `note` VARCHAR(255) DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_page_version` (`page_id`, `version_number`),
            KEY `idx_pageversion_created` (`created_at`),
            CONSTRAINT `fk_pageversion_page` FOREIGN KEY (`page_id`) REFERENCES `cms_pages` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 7. cms_collections
        $db->query("CREATE TABLE `cms_collections` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `collection_key` VARCHAR(50) NOT NULL,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `requires_approval` TINYINT(1) NOT NULL DEFAULT 0,
            `enables_categories` TINYINT(1) NOT NULL DEFAULT 1,
            `enables_tags` TINYINT(1) NOT NULL DEFAULT 1,
            `default_sitemap_priority` DECIMAL(2,1) DEFAULT 0.6,
            `default_changefreq` ENUM('always','hourly','daily','weekly','monthly','yearly','never') DEFAULT 'weekly',
            `sort_order` INT NOT NULL DEFAULT 0,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_collection_key` (`collection_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 8. cms_collection_translations
        $db->query("CREATE TABLE `cms_collection_translations` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `collection_id` INT UNSIGNED NOT NULL,
            `language_id` INT UNSIGNED NOT NULL,
            `slug` VARCHAR(150) NOT NULL,
            `name` VARCHAR(150) NOT NULL,
            `description` TEXT,
            `listing_title` VARCHAR(255) DEFAULT NULL,
            `listing_intro` TEXT DEFAULT NULL,
            `default_meta_title` VARCHAR(255) DEFAULT NULL,
            `default_meta_description` VARCHAR(500) DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_collection_lang` (`collection_id`, `language_id`),
            UNIQUE KEY `uk_collection_slug_lang` (`language_id`, `slug`),
            KEY `idx_colltrans_lang` (`language_id`),
            CONSTRAINT `fk_colltrans_collection` FOREIGN KEY (`collection_id`) REFERENCES `cms_collections` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_colltrans_lang` FOREIGN KEY (`language_id`) REFERENCES `cms_languages` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 9. cms_categories
        $db->query("CREATE TABLE `cms_categories` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `collection_id` INT UNSIGNED NOT NULL,
            `parent_id` INT UNSIGNED DEFAULT NULL,
            `sort_order` INT NOT NULL DEFAULT 0,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            `deleted_at` DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_cat_collection` (`collection_id`, `is_active`, `sort_order`),
            KEY `idx_cat_parent` (`parent_id`),
            CONSTRAINT `fk_cat_collection` FOREIGN KEY (`collection_id`) REFERENCES `cms_collections` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_cat_parent` FOREIGN KEY (`parent_id`) REFERENCES `cms_categories` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 10. cms_category_translations
        $db->query("CREATE TABLE `cms_category_translations` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `category_id` INT UNSIGNED NOT NULL,
            `language_id` INT UNSIGNED NOT NULL,
            `slug` VARCHAR(150) NOT NULL,
            `name` VARCHAR(150) NOT NULL,
            `description` TEXT,
            `meta_title` VARCHAR(255) DEFAULT NULL,
            `meta_description` VARCHAR(500) DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_cat_lang` (`category_id`, `language_id`),
            KEY `idx_cattrans_slug` (`language_id`, `slug`),
            CONSTRAINT `fk_cattrans_cat` FOREIGN KEY (`category_id`) REFERENCES `cms_categories` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_cattrans_lang` FOREIGN KEY (`language_id`) REFERENCES `cms_languages` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 11. cms_tags
        $db->query("CREATE TABLE `cms_tags` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_tag_active` (`is_active`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 12. cms_tag_translations
        $db->query("CREATE TABLE `cms_tag_translations` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `tag_id` INT UNSIGNED NOT NULL,
            `language_id` INT UNSIGNED NOT NULL,
            `slug` VARCHAR(100) NOT NULL,
            `name` VARCHAR(100) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_tag_lang` (`tag_id`, `language_id`),
            UNIQUE KEY `uk_tag_slug_lang` (`language_id`, `slug`),
            CONSTRAINT `fk_tagtrans_tag` FOREIGN KEY (`tag_id`) REFERENCES `cms_tags` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_tagtrans_lang` FOREIGN KEY (`language_id`) REFERENCES `cms_languages` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 13. cms_entries
        $db->query("CREATE TABLE `cms_entries` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `collection_id` INT UNSIGNED NOT NULL,
            `author_id` INT UNSIGNED DEFAULT NULL,
            `workflow_status` ENUM('draft','in_review','approved','published','archived') NOT NULL DEFAULT 'draft',
            `published_at` DATETIME DEFAULT NULL,
            `scheduled_at` DATETIME DEFAULT NULL,
            `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
            `view_count` INT UNSIGNED NOT NULL DEFAULT 0,
            `sort_order` INT NOT NULL DEFAULT 0,
            `sitemap_priority` DECIMAL(2,1) DEFAULT NULL,
            `sitemap_changefreq` ENUM('always','hourly','daily','weekly','monthly','yearly','never') DEFAULT NULL,
            `is_in_sitemap` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            `deleted_at` DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_entry_collection_status` (`collection_id`, `workflow_status`, `deleted_at`),
            KEY `idx_entry_author` (`author_id`),
            KEY `idx_entry_featured` (`is_featured`, `workflow_status`),
            KEY `idx_entry_sort` (`collection_id`, `sort_order`),
            CONSTRAINT `fk_entry_collection` FOREIGN KEY (`collection_id`) REFERENCES `cms_collections` (`id`) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 14. cms_entry_translations
        $db->query("CREATE TABLE `cms_entry_translations` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `entry_id` INT UNSIGNED NOT NULL,
            `language_id` INT UNSIGNED NOT NULL,
            `slug` VARCHAR(150) NOT NULL,
            `title` VARCHAR(255) NOT NULL,
            `excerpt` VARCHAR(500) DEFAULT NULL,
            `featured_file_id` INT UNSIGNED DEFAULT NULL,
            `meta_title` VARCHAR(255) DEFAULT NULL,
            `meta_description` VARCHAR(500) DEFAULT NULL,
            `og_image_file_id` INT UNSIGNED DEFAULT NULL,
            `og_type` VARCHAR(50) DEFAULT 'article',
            `canonical_url` VARCHAR(500) DEFAULT NULL,
            `robots` VARCHAR(100) DEFAULT NULL,
            `schema_data` JSON DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_entry_lang` (`entry_id`, `language_id`),
            UNIQUE KEY `uk_entry_slug_lang` (`language_id`, `slug`),
            KEY `idx_entrytrans_lang` (`language_id`),
            FULLTEXT KEY `ft_entry_search` (`title`, `excerpt`),
            CONSTRAINT `fk_entrytrans_entry` FOREIGN KEY (`entry_id`) REFERENCES `cms_entries` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_entrytrans_lang` FOREIGN KEY (`language_id`) REFERENCES `cms_languages` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 15. cms_entry_versions
        $db->query("CREATE TABLE `cms_entry_versions` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `entry_id` INT UNSIGNED NOT NULL,
            `version_number` INT NOT NULL,
            `snapshot` JSON NOT NULL,
            `created_by` INT UNSIGNED DEFAULT NULL,
            `note` VARCHAR(255) DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_entry_version` (`entry_id`, `version_number`),
            KEY `idx_entryversion_created` (`created_at`),
            CONSTRAINT `fk_entryversion_entry` FOREIGN KEY (`entry_id`) REFERENCES `cms_entries` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 16. cms_entry_tags
        $db->query("CREATE TABLE `cms_entry_tags` (
            `entry_id` INT UNSIGNED NOT NULL,
            `tag_id` INT UNSIGNED NOT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`entry_id`, `tag_id`),
            KEY `idx_entrytags_tag` (`tag_id`),
            CONSTRAINT `fk_entrytags_entry` FOREIGN KEY (`entry_id`) REFERENCES `cms_entries` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_entrytags_tag` FOREIGN KEY (`tag_id`) REFERENCES `cms_tags` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 17. cms_entry_categories (Pivot N:M table v4 replacement for category_id)
        $db->query("CREATE TABLE `cms_entry_categories` (
            `entry_id` INT UNSIGNED NOT NULL,
            `category_id` INT UNSIGNED NOT NULL,
            `sort_order` INT NOT NULL DEFAULT 0,
            PRIMARY KEY (`entry_id`, `category_id`),
            KEY `idx_entrycats_category` (`category_id`),
            CONSTRAINT `fk_entrycats_entry` FOREIGN KEY (`entry_id`) REFERENCES `cms_entries` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_entrycats_cat` FOREIGN KEY (`category_id`) REFERENCES `cms_categories` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 18. cms_entry_related
        $db->query("CREATE TABLE `cms_entry_related` (
            `entry_id` INT UNSIGNED NOT NULL,
            `related_entry_id` INT UNSIGNED NOT NULL,
            `relation_type` ENUM('related','recommended','prerequisite','sequel') NOT NULL DEFAULT 'related',
            `sort_order` INT NOT NULL DEFAULT 0,
            PRIMARY KEY (`entry_id`, `related_entry_id`),
            KEY `idx_related_target` (`related_entry_id`),
            CONSTRAINT `fk_related_entry` FOREIGN KEY (`entry_id`) REFERENCES `cms_entries` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_related_target` FOREIGN KEY (`related_entry_id`) REFERENCES `cms_entries` (`id`) ON DELETE CASCADE,
            CONSTRAINT `chk_related_not_self` CHECK (`entry_id` <> `related_entry_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 19. cms_content_blocks
        $db->query("CREATE TABLE `cms_content_blocks` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `block_key` VARCHAR(50) NOT NULL,
            `name` VARCHAR(100) NOT NULL,
            `description` TEXT,
            `category` VARCHAR(50) NOT NULL DEFAULT 'general',
            `icon` VARCHAR(50) DEFAULT NULL,
            `schema_definition` JSON NOT NULL,
            `supports_pages` TINYINT(1) NOT NULL DEFAULT 1,
            `supports_entries` TINYINT(1) NOT NULL DEFAULT 1,
            `is_container` TINYINT(1) NOT NULL DEFAULT 0,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `sort_order` INT NOT NULL DEFAULT 0,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_block_key` (`block_key`),
            KEY `idx_block_category` (`category`, `sort_order`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 20. cms_block_instances
        $db->query("CREATE TABLE `cms_block_instances` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `block_id` INT UNSIGNED NOT NULL,
            `owner_type` ENUM('page','entry') NOT NULL,
            `owner_id` INT UNSIGNED NOT NULL,
            `parent_instance_id` INT UNSIGNED DEFAULT NULL,
            `sort_order` INT NOT NULL DEFAULT 0,
            `column_index` TINYINT UNSIGNED DEFAULT NULL,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `block_config` JSON DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_blockinst_owner` (`owner_type`, `owner_id`, `sort_order`),
            KEY `idx_blockinst_parent` (`parent_instance_id`, `sort_order`),
            KEY `idx_blockinst_block` (`block_id`),
            CONSTRAINT `fk_blockinst_block` FOREIGN KEY (`block_id`) REFERENCES `cms_content_blocks` (`id`) ON DELETE RESTRICT,
            CONSTRAINT `fk_blockinst_parent` FOREIGN KEY (`parent_instance_id`) REFERENCES `cms_block_instances` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 21. cms_block_instance_translations
        $db->query("CREATE TABLE `cms_block_instance_translations` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `instance_id` INT UNSIGNED NOT NULL,
            `language_id` INT UNSIGNED NOT NULL,
            `block_data` JSON NOT NULL,
            `is_published` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_blocktrans_lang` (`instance_id`, `language_id`),
            KEY `idx_blocktrans_lang` (`language_id`),
            CONSTRAINT `fk_blocktrans_inst` FOREIGN KEY (`instance_id`) REFERENCES `cms_block_instances` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_blocktrans_lang` FOREIGN KEY (`language_id`) REFERENCES `cms_languages` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 22. cms_menus
        $db->query("CREATE TABLE `cms_menus` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `menu_key` VARCHAR(50) NOT NULL,
            `location` VARCHAR(50) NOT NULL DEFAULT 'header',
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            `deleted_at` DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_menu_key` (`menu_key`),
            KEY `idx_menu_location` (`location`, `is_active`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 23. cms_menu_translations
        $db->query("CREATE TABLE `cms_menu_translations` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `menu_id` INT UNSIGNED NOT NULL,
            `language_id` INT UNSIGNED NOT NULL,
            `name` VARCHAR(150) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_menu_lang` (`menu_id`, `language_id`),
            CONSTRAINT `fk_menutrans_menu` FOREIGN KEY (`menu_id`) REFERENCES `cms_menus` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_menutrans_lang` FOREIGN KEY (`language_id`) REFERENCES `cms_languages` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 24. cms_menu_items
        $db->query("CREATE TABLE `cms_menu_items` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `menu_id` INT UNSIGNED NOT NULL,
            `parent_id` INT UNSIGNED DEFAULT NULL,
            `link_type` ENUM('page','entry','collection_listing','custom_url','no_link') NOT NULL,
            `page_id` INT UNSIGNED DEFAULT NULL,
            `entry_id` INT UNSIGNED DEFAULT NULL,
            `collection_id` INT UNSIGNED DEFAULT NULL,
            `link_target` ENUM('_self','_blank') NOT NULL DEFAULT '_self',
            `icon` VARCHAR(50) DEFAULT NULL,
            `css_class` VARCHAR(100) DEFAULT NULL,
            `sort_order` INT NOT NULL DEFAULT 0,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_menuitem_menu_parent` (`menu_id`, `parent_id`, `sort_order`),
            KEY `idx_menuitem_page` (`page_id`),
            KEY `idx_menuitem_entry` (`entry_id`),
            KEY `idx_menuitem_collection` (`collection_id`),
            CONSTRAINT `fk_menuitem_menu` FOREIGN KEY (`menu_id`) REFERENCES `cms_menus` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_menuitem_parent` FOREIGN KEY (`parent_id`) REFERENCES `cms_menu_items` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_menuitem_page` FOREIGN KEY (`page_id`) REFERENCES `cms_pages` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_menuitem_entry` FOREIGN KEY (`entry_id`) REFERENCES `cms_entries` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_menuitem_collection` FOREIGN KEY (`collection_id`) REFERENCES `cms_collections` (`id`) ON DELETE CASCADE,
            CONSTRAINT `chk_menuitem_link` CHECK (
                (`link_type` = 'page' AND `page_id` IS NOT NULL AND `entry_id` IS NULL AND `collection_id` IS NULL)
                OR (`link_type` = 'entry' AND `entry_id` IS NOT NULL AND `page_id` IS NULL AND `collection_id` IS NULL)
                OR (`link_type` = 'collection_listing' AND `collection_id` IS NOT NULL AND `page_id` IS NULL AND `entry_id` IS NULL)
                OR (`link_type` = 'custom_url' AND `page_id` IS NULL AND `entry_id` IS NULL AND `collection_id` IS NULL)
                OR (`link_type` = 'no_link' AND `page_id` IS NULL AND `entry_id` IS NULL AND `collection_id` IS NULL)
            )
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 25. cms_menu_item_translations
        $db->query("CREATE TABLE `cms_menu_item_translations` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `menu_item_id` INT UNSIGNED NOT NULL,
            `language_id` INT UNSIGNED NOT NULL,
            `label` VARCHAR(150) NOT NULL,
            `custom_url` VARCHAR(500) DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_menuitem_lang` (`menu_item_id`, `language_id`),
            KEY `idx_menuitemtrans_lang` (`language_id`),
            CONSTRAINT `fk_menuitemtrans_item` FOREIGN KEY (`menu_item_id`) REFERENCES `cms_menu_items` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_menuitemtrans_lang` FOREIGN KEY (`language_id`) REFERENCES `cms_languages` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 26. cms_file_translations
        $db->query("CREATE TABLE `cms_file_translations` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `file_id` INT UNSIGNED NOT NULL,
            `language_id` INT UNSIGNED NOT NULL,
            `alt_text` VARCHAR(255) DEFAULT NULL,
            `caption` VARCHAR(500) DEFAULT NULL,
            `title` VARCHAR(255) DEFAULT NULL,
            `credit` VARCHAR(255) DEFAULT NULL,
            `description` TEXT DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_file_lang` (`file_id`, `language_id`),
            KEY `idx_filetrans_lang` (`language_id`),
            CONSTRAINT `fk_filetrans_lang` FOREIGN KEY (`language_id`) REFERENCES `cms_languages` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 27. cms_redirects
        $db->query("CREATE TABLE `cms_redirects` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `old_path` VARCHAR(500) NOT NULL,
            `new_url` VARCHAR(500) NOT NULL,
            `redirect_type` SMALLINT UNSIGNED NOT NULL DEFAULT 301,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `hit_count` INT UNSIGNED NOT NULL DEFAULT 0,
            `last_hit_at` DATETIME DEFAULT NULL,
            `note` VARCHAR(255) DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_redirect_old_path` (`old_path`),
            KEY `idx_redirect_active` (`is_active`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 28. cms_slug_redirects
        $db->query("CREATE TABLE `cms_slug_redirects` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `entity_type` ENUM('page','entry','category','tag','collection') NOT NULL,
            `entity_id` INT UNSIGNED NOT NULL,
            `language_id` INT UNSIGNED NOT NULL,
            `old_slug` VARCHAR(150) NOT NULL,
            `old_full_path` VARCHAR(500) NOT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_slugredir_lookup` (`language_id`, `old_full_path`),
            KEY `idx_slugredir_entity` (`entity_type`, `entity_id`),
            CONSTRAINT `fk_slugredir_lang` FOREIGN KEY (`language_id`) REFERENCES `cms_languages` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 29. cms_form_submissions
        $db->query("CREATE TABLE `cms_form_submissions` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `form_key` VARCHAR(50) NOT NULL,
            `page_id` INT UNSIGNED DEFAULT NULL,
            `language_id` INT UNSIGNED DEFAULT NULL,
            `data_json` JSON NOT NULL,
            `status` ENUM('new','read','replied','spam','archived') NOT NULL DEFAULT 'new',
            `ip_address` VARCHAR(45) DEFAULT NULL,
            `user_agent` VARCHAR(500) DEFAULT NULL,
            `is_anonymized` TINYINT(1) NOT NULL DEFAULT 0,
            `anonymized_at` DATETIME DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_subm_form_status` (`form_key`, `status`, `created_at`),
            KEY `idx_subm_page` (`page_id`),
            KEY `idx_subm_anonymized` (`is_anonymized`),
            CONSTRAINT `fk_subm_page` FOREIGN KEY (`page_id`) REFERENCES `cms_pages` (`id`) ON DELETE SET NULL,
            CONSTRAINT `fk_subm_lang` FOREIGN KEY (`language_id`) REFERENCES `cms_languages` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 30. cms_search_index
        $db->query("CREATE TABLE `cms_search_index` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `entity_type` ENUM('page','entry') NOT NULL,
            `entity_id` INT UNSIGNED NOT NULL,
            `language_id` INT UNSIGNED NOT NULL,
            `url` VARCHAR(500) NOT NULL,
            `title` VARCHAR(500) NOT NULL,
            `excerpt` TEXT,
            `body` LONGTEXT,
            `collection_id` INT UNSIGNED DEFAULT NULL,
            `category_id` INT UNSIGNED DEFAULT NULL,
            `tags_csv` VARCHAR(500) DEFAULT NULL,
            `is_published` TINYINT(1) NOT NULL DEFAULT 1,
            `published_at` DATETIME DEFAULT NULL,
            `reindexed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_search_entity` (`entity_type`, `entity_id`, `language_id`),
            KEY `idx_search_lang_pub` (`language_id`, `is_published`, `published_at`),
            KEY `idx_search_collection` (`collection_id`),
            FULLTEXT KEY `ft_search_content` (`title`, `excerpt`, `body`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Re-enable foreign keys
        $db->query('SET FOREIGN_KEY_CHECKS = 1');
    }

    public function down(): void
    {
        $db = $this->db;

        $db->query('SET FOREIGN_KEY_CHECKS = 0');

        $tables = [
            'cms_search_index',
            'cms_form_submissions',
            'cms_slug_redirects',
            'cms_redirects',
            'cms_file_translations',
            'cms_menu_item_translations',
            'cms_menu_items',
            'cms_menu_translations',
            'cms_menus',
            'cms_block_instance_translations',
            'cms_block_instances',
            'cms_content_blocks',
            'cms_entry_related',
            'cms_entry_categories',
            'cms_entry_tags',
            'cms_entry_versions',
            'cms_entry_translations',
            'cms_entries',
            'cms_tag_translations',
            'cms_tags',
            'cms_category_translations',
            'cms_categories',
            'cms_collection_translations',
            'cms_collections',
            'cms_page_versions',
            'cms_page_translations',
            'cms_pages',
            'cms_setting_translations',
            'cms_settings',
            'cms_languages',
        ];

        foreach ($tables as $table) {
            $db->query("DROP TABLE IF EXISTS `{$table}`");
        }

        $db->query('SET FOREIGN_KEY_CHECKS = 1');
    }
}
