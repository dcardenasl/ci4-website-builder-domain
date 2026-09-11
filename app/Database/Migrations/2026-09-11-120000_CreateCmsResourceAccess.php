<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Domain-owned grants for pages, entries and collections. */
class CreateCmsResourceAccess extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'resource_type' => ['type' => 'VARCHAR', 'constraint' => 32],
            'resource_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'user_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'access_level' => ['type' => 'ENUM', 'constraint' => ['read', 'write', 'admin']],
            'created_by' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => false],
            'updated_at' => ['type' => 'DATETIME', 'null' => false],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['resource_type', 'resource_id', 'user_id'], 'uk_cms_resource_access_subject');
        $this->forge->addKey(['user_id', 'resource_type', 'access_level'], false, false, 'idx_cms_resource_access_subject');
        $this->forge->addKey(['resource_type', 'resource_id', 'access_level'], false, false, 'idx_cms_resource_access_resource');
        $this->forge->createTable('cms_resource_access', false, ['ENGINE' => 'InnoDB']);
    }

    public function down(): void
    {
        $this->forge->dropTable('cms_resource_access', true);
    }
}
