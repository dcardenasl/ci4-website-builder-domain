<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAuditLogsTable extends Migration
{
    public function up(): void
    {
        /** @var \CodeIgniter\Database\BaseConnection $db */
        $db = $this->db;

        if ($db->tableExists('audit_logs')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => true,
            ],
            'action' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'entity_type' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'entity_id' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => true,
            ],
            'old_values' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'new_values' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'ip_address' => [
                'type' => 'VARCHAR',
                'constraint' => 45,
            ],
            'user_agent' => [
                'type' => 'VARCHAR',
                'constraint' => 500,
                'null' => true,
            ],
            'result' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'default' => 'success',
            ],
            'severity' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'default' => 'info',
            ],
            'request_id' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => true,
            ],
            'metadata' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'created_at' => [
                'type'    => 'DATETIME',
                'null'    => false,
                'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['user_id', 'entity_type', 'entity_id']);
        $this->forge->addKey('created_at');
        $this->forge->addKey(['action', 'created_at'], false, false, 'idx_audit_action_created_at');
        $this->forge->addKey(['severity', 'created_at'], false, false, 'idx_audit_severity_created_at');
        $this->forge->addKey(['result', 'created_at'], false, false, 'idx_audit_result_created_at');
        $this->forge->addKey('request_id', false, false, 'idx_audit_request_id');
        // No FK to users — website builder apps don't own a users table; the user_id
        // reflects the hub user surfaced via DomainAuthFilter.
        $this->forge->createTable('audit_logs');
    }

    public function down(): void
    {
        /** @var \CodeIgniter\Database\BaseConnection $db */
        $db = $this->db;

        if (! $db->tableExists('audit_logs')) {
            return;
        }

        $this->forge->dropTable('audit_logs', true);
    }
}
