<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCmsSettingConnections extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'setting_id'  => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'entity_type' => [
                'type'       => 'ENUM',
                'constraint' => ['block_type', 'form', 'collection', 'module'],
            ],
            'entity_key'  => ['type' => 'VARCHAR', 'constraint' => 100],
            'usage_label' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null],
        ]);
        $this->forge->addField('`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['setting_id', 'entity_type', 'entity_key'], 'uk_setting_conn');
        $this->forge->addForeignKey('setting_id', 'cms_settings', 'id', '', 'CASCADE', 'fk_settconn_setting');

        $this->forge->createTable('cms_setting_connections', false, ['ENGINE' => 'InnoDB']);
    }

    public function down(): void
    {
        /** @var \CodeIgniter\Database\BaseConnection $db */
        $db = $this->db;

        $db->disableForeignKeyChecks();
        $this->forge->dropTable('cms_setting_connections', true);
        $db->enableForeignKeyChecks();
    }
}
