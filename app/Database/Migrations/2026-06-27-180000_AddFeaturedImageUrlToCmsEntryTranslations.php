<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFeaturedImageUrlToCmsEntryTranslations extends Migration
{
    public function up(): void
    {
        $fields = [
            'featured_image_url' => [
                'type' => 'VARCHAR',
                'constraint' => 2048,
                'null' => true,
                'after' => 'featured_file_id',
            ],
        ];

        $this->forge->addColumn('cms_entry_translations', $fields);
    }

    public function down(): void
    {
        $this->forge->dropColumn('cms_entry_translations', 'featured_image_url');
    }
}
