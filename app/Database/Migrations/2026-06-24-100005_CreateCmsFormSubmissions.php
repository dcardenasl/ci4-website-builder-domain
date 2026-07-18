<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCmsFormSubmissions extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'            => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'form_id'       => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'form_key'      => ['type' => 'VARCHAR', 'constraint' => 50],
            'page_id'       => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'language_id'   => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'data_json'     => ['type' => 'JSON'],
            'status'        => ['type' => 'ENUM', 'constraint' => ['new', 'read', 'replied', 'spam', 'archived'], 'default' => 'new'],
            'ip_address'    => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'user_agent'    => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'is_anonymized' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'anonymized_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addField('`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->forge->addField('`updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP');

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('form_id', false, false, 'idx_subm_form');
        $this->forge->addKey(['form_key', 'status', 'created_at'], false, false, 'idx_subm_form_status');
        $this->forge->addKey('page_id', false, false, 'idx_subm_page');
        $this->forge->addKey('is_anonymized', false, false, 'idx_subm_anonymized');
        $this->forge->addForeignKey('form_id', 'cms_forms', 'id', '', 'SET NULL', 'fk_form_submissions_form_id');
        $this->forge->addForeignKey('page_id', 'cms_pages', 'id', '', 'SET NULL', 'fk_subm_page');
        $this->forge->addForeignKey('language_id', 'cms_languages', 'id', '', 'SET NULL', 'fk_subm_lang');

        $this->forge->createTable('cms_form_submissions', false, ['ENGINE' => 'InnoDB']);
    }

    public function down(): void
    {
        /** @var \CodeIgniter\Database\BaseConnection $db */
        $db = $this->db;

        $db->disableForeignKeyChecks();
        $this->forge->dropTable('cms_form_submissions', true);
        $db->enableForeignKeyChecks();
    }
}
