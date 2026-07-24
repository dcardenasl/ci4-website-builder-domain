<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCmsSettings extends Migration
{
    public function up(): void
    {
        /** @var \CodeIgniter\Database\BaseConnection $db */
        $db = $this->db;

        if (! $db->tableExists('cms_settings')) {
            $this->forge->addField([
                'id'              => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
                'setting_key'     => ['type' => 'VARCHAR', 'constraint' => 100],
                'setting_value'   => ['type' => 'LONGTEXT', 'null' => true],
                'setting_meta'    => ['type' => 'TEXT', 'null' => true],
                'setting_type'    => ['type' => 'ENUM', 'constraint' => ['string', 'int', 'bool', 'json', 'file_id'], 'default' => 'string'],
                'input_type'      => [
                    'type' => 'ENUM',
                    'constraint' => ['text', 'textarea', 'richtext', 'url', 'email', 'phone', 'color', 'number', 'boolean', 'image', 'file', 'select', 'code', 'slug'],
                    'default' => 'text',
                ],
                'options_json'    => ['type' => 'JSON', 'null' => true],
                'is_required'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'is_readonly'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'setting_group'   => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'general'],
                'is_translatable' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'sort_order'      => ['type' => 'INT', 'default' => 0],
                'description'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'is_public'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'is_active'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            ]);
            $this->forge->addField('`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
            $this->forge->addField('`updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP');

            $this->forge->addPrimaryKey('id');
            $this->forge->addUniqueKey('setting_key', 'uk_setting_key');
            $this->forge->addKey(['setting_group', 'sort_order'], false, false, 'idx_setting_group');
            $this->forge->addKey(['is_public', 'is_active', 'sort_order'], false, false, 'idx_setting_public_active');

            $this->forge->createTable('cms_settings', false, ['ENGINE' => 'InnoDB']);
        }

        if (! $db->tableExists('cms_setting_translations')) {
            $this->forge->addField([
                'id'            => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
                'setting_id'    => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
                'language_id'   => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
                'setting_value' => ['type' => 'LONGTEXT', 'null' => true],
                'label'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'placeholder'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'help_text'     => ['type' => 'TEXT', 'null' => true],
            ]);

            $this->forge->addPrimaryKey('id');
            $this->forge->addUniqueKey(['setting_id', 'language_id'], 'uk_setting_lang');
            $this->forge->addKey('language_id', false, false, 'idx_settrans_lang');
            $this->forge->addForeignKey('setting_id', 'cms_settings', 'id', '', 'CASCADE', 'fk_settrans_setting');
            $this->forge->addForeignKey('language_id', 'cms_languages', 'id', '', 'CASCADE', 'fk_settrans_lang');

            $this->forge->createTable('cms_setting_translations', false, ['ENGINE' => 'InnoDB']);
        }
    }

    public function down(): void
    {
        /** @var \CodeIgniter\Database\BaseConnection $db */
        $db = $this->db;

        $db->disableForeignKeyChecks();
        if ($db->tableExists('cms_setting_translations')) {
            $this->forge->dropTable('cms_setting_translations', true);
        }
        if ($db->tableExists('cms_settings')) {
            $this->forge->dropTable('cms_settings', true);
        }
        $db->enableForeignKeyChecks();
    }
}
