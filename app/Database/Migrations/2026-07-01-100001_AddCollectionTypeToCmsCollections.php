<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCollectionTypeToCmsCollections extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('cms_collections', [
            'collection_type' => [
                'type' => 'ENUM',
                'constraint' => ['blog', 'news', 'portfolio', 'services', 'other'],
                'null' => false,
                'default' => 'other',
                'after' => 'collection_key',
            ],
        ]);

        $this->db->query("UPDATE `cms_collections` SET `collection_type` = 'news' WHERE `collection_key` IN ('noticias', 'news')");
    }

    public function down(): void
    {
        $this->forge->dropColumn('cms_collections', 'collection_type');
    }
}
