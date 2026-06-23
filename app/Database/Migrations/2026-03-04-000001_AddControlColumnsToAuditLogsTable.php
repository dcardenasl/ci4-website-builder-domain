<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddControlColumnsToAuditLogsTable extends Migration
{
    public function up(): void
    {
        $columns = $this->db->query(
            "SELECT column_name AS col_name FROM information_schema.COLUMNS
             WHERE table_schema = DATABASE() AND table_name = 'audit_logs'"
        )->getResultArray();

        $existing = array_fill_keys(array_map(static fn (array $row): string => (string) $row['col_name'], $columns), true);
        $additions = [];

        if (! isset($existing['result'])) {
            $additions['result'] = [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => false,
                'default' => 'success',
                'after' => 'user_agent',
            ];
        }

        if (! isset($existing['severity'])) {
            $additions['severity'] = [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => false,
                'default' => 'info',
                'after' => 'result',
            ];
        }

        if (! isset($existing['request_id'])) {
            $additions['request_id'] = [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => true,
                'after' => 'severity',
            ];
        }

        if (! isset($existing['metadata'])) {
            $additions['metadata'] = [
                'type' => 'JSON',
                'null' => true,
                'after' => 'request_id',
            ];
        }

        if ($additions !== []) {
            $this->forge->addColumn('audit_logs', $additions);
        }

        $indexExists = function (string $indexName) {
            return $this->db->query(
                "SELECT COUNT(*) AS cnt FROM information_schema.STATISTICS
                 WHERE table_schema = DATABASE()
                   AND table_name = 'audit_logs'
                   AND index_name = " . $this->db->escape($indexName)
            );
        };

        if ((int) $indexExists('idx_audit_action_created_at')->getRowArray()['cnt'] === 0) {
            $this->forge->addKey(['action', 'created_at'], false, false, 'idx_audit_action_created_at');
        }
        if ((int) $indexExists('idx_audit_severity_created_at')->getRowArray()['cnt'] === 0) {
            $this->forge->addKey(['severity', 'created_at'], false, false, 'idx_audit_severity_created_at');
        }
        if ((int) $indexExists('idx_audit_result_created_at')->getRowArray()['cnt'] === 0) {
            $this->forge->addKey(['result', 'created_at'], false, false, 'idx_audit_result_created_at');
        }
        if ((int) $indexExists('idx_audit_request_id')->getRowArray()['cnt'] === 0) {
            $this->forge->addKey('request_id', false, false, 'idx_audit_request_id');
        }
        $this->forge->processIndexes('audit_logs');
    }

    public function down(): void
    {
        // Intentionally left as a no-op.
        // This migration is only used to move the schema forward; rollback is
        // not part of the supported setup flow.
    }
}
