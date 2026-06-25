<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddBlockTemplateToCmsCollections extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('cms_collections', [
            'block_template' => [
                'type'    => 'LONGTEXT',
                'null'    => true,
                'default' => null,
                'comment' => 'JSON v1.0 schema defining required/optional blocks inherited by entries',
                'after'   => 'default_changefreq',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('cms_collections', 'block_template');
    }
}
