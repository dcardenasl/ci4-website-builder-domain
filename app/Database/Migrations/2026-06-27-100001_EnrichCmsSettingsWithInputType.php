<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class EnrichCmsSettingsWithInputType extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('cms_settings', [
            'input_type' => [
                'type'       => 'ENUM',
                'constraint' => [
                    'text', 'textarea', 'richtext', 'url', 'email', 'phone',
                    'color', 'number', 'boolean', 'image', 'file', 'select',
                    'code', 'slug',
                ],
                'default'    => 'text',
                'null'       => false,
                'after'      => 'setting_type',
            ],
            'options_json' => [
                'type'    => 'JSON',
                'null'    => true,
                'default' => null,
                'after'   => 'input_type',
            ],
            'is_required' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'null'       => false,
                'after'      => 'options_json',
            ],
            'is_readonly' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'null'       => false,
                'after'      => 'is_required',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('cms_settings', ['input_type', 'options_json', 'is_required', 'is_readonly']);
    }
}
