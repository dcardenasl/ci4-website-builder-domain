<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUiMetaToCmsSettingTranslations extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('cms_setting_translations', [
            'label' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'default'    => null,
                'after'      => 'setting_value',
            ],
            'placeholder' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'default'    => null,
                'after'      => 'label',
            ],
            'help_text' => [
                'type'    => 'TEXT',
                'null'    => true,
                'default' => null,
                'after'   => 'placeholder',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('cms_setting_translations', ['label', 'placeholder', 'help_text']);
    }
}
