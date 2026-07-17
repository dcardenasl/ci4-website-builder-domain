<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddOgImageUrlToCmsPageTranslations extends Migration
{
    public function up(): void
    {
        if (! $this->db->fieldExists('og_image_url', 'cms_page_translations')) {
            $this->forge->addColumn('cms_page_translations', [
                'og_image_url' => [
                    'type' => 'VARCHAR',
                    'constraint' => 2048,
                    'null' => true,
                    'after' => 'og_image_file_id',
                ],
            ]);
        }
    }

    public function down(): void
    {
        if ($this->db->fieldExists('og_image_url', 'cms_page_translations')) {
            $this->forge->dropColumn('cms_page_translations', 'og_image_url');
        }
    }
}
