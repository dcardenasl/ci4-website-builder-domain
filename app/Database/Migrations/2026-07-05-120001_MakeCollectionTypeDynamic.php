<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class MakeCollectionTypeDynamic extends Migration
{
    public function up(): void
    {
        $this->forge->modifyColumn('cms_collections', [
            'collection_type' => [
                'name' => 'collection_type',
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false,
                'default' => 'other',
            ],
        ]);
    }

    public function down(): void
    {
        $this->db->table('cms_collections')
            ->whereNotIn('collection_type', ['blog', 'news', 'portfolio', 'services', 'other'])
            ->set('collection_type', 'other')
            ->update();

        $this->forge->modifyColumn('cms_collections', [
            'collection_type' => [
                'name' => 'collection_type',
                'type' => 'ENUM',
                'constraint' => ['blog', 'news', 'portfolio', 'services', 'other'],
                'null' => false,
                'default' => 'other',
            ],
        ]);
    }
}
