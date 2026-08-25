<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Generic public-slug sidecar. Existing resources are backfilled by an explicit command. */
final class CreatePublicSlugs extends Migration
{
    public function up(): void
    {
        /** @var \CodeIgniter\Database\BaseConnection $db */
        $db = $this->db;

        if (! $db->tableExists('public_slugs')) {
            $this->forge->addField([
                'id'            => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
                'resource_type' => ['type' => 'VARCHAR', 'constraint' => 80],
                'resource_id'   => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
                'locale'        => ['type' => 'VARCHAR', 'constraint' => 35],
                'slug'          => ['type' => 'VARCHAR', 'constraint' => 191],
            ]);
            $this->forge->addField('`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
            $this->forge->addField('`updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP');
            $this->forge->addPrimaryKey('id');
            $this->forge->addUniqueKey(['resource_type', 'locale', 'slug'], 'uk_public_slug_route');
            $this->forge->addUniqueKey(['resource_type', 'resource_id', 'locale'], 'uk_public_slug_resource_locale');
            $this->forge->addKey(['resource_type', 'resource_id'], false, false, 'idx_public_slug_resource');
            $this->forge->createTable('public_slugs', false, ['ENGINE' => 'InnoDB']);
        }

    }

    public function down(): void
    {
        $this->forge->dropTable('public_slugs', true);
    }
}
