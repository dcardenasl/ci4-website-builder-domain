<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class MakeCollectionTypeDynamic extends Migration
{
    public function up(): void
    {
        // See 2026-07-01-100001_AddCollectionTypeToCmsCollections for why this
        // reset is required: fieldExists() otherwise trusts a stale per-connection
        // schema cache that predates that earlier migration's DDL in this run.
        $this->db->resetDataCache();

        if (! $this->db->fieldExists('collection_type', 'cms_collections')) {
            try {
                $this->forge->addColumn('cms_collections', [
                    'collection_type' => [
                        'type' => 'VARCHAR',
                        'constraint' => 50,
                        'null' => false,
                        'default' => 'other',
                        'after' => 'collection_key',
                    ],
                ]);
            } catch (\Throwable) {
                // Ignore when the column is already present or the schema is partially applied.
            }
        } else {
            try {
                $this->forge->modifyColumn('cms_collections', [
                    'collection_type' => [
                        'name' => 'collection_type',
                        'type' => 'VARCHAR',
                        'constraint' => 50,
                        'null' => false,
                        'default' => 'other',
                    ],
                ]);
            } catch (\Throwable) {
                // Ignore when the column already matches the desired shape.
            }
        }
    }

    public function down(): void
    {
        $this->db->resetDataCache();

        if (! $this->db->fieldExists('collection_type', 'cms_collections')) {
            return;
        }

        try {
            $this->db->table('cms_collections')
                ->whereNotIn('collection_type', ['blog', 'news', 'portfolio', 'services', 'other'])
                ->set('collection_type', 'other')
                ->update();
        } catch (\Throwable) {
            // Ignore when the column is missing in a partially reverted schema.
        }

        try {
            $this->forge->modifyColumn('cms_collections', [
                'collection_type' => [
                    'name' => 'collection_type',
                    'type' => 'ENUM',
                    'constraint' => ['blog', 'news', 'portfolio', 'services', 'other'],
                    'null' => false,
                    'default' => 'other',
                ],
            ]);
        } catch (\Throwable) {
            // Ignore when the column already matches the reverted shape.
        }
    }
}
