<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUiMetaToCmsSettingTranslations extends Migration
{
    public function up(): void
    {
        // See 2026-06-27-180000_AddFeaturedImageUrlToCmsEntryTranslations —
        // fieldExists() needs a fresh schema read within a multi-migration run.
        $this->db->resetDataCache();

        $fields = [];

        if (! $this->db->fieldExists('label', 'cms_setting_translations')) {
            $fields['label'] = [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'default'    => null,
                'after'      => 'setting_value',
            ];
        }

        if (! $this->db->fieldExists('placeholder', 'cms_setting_translations')) {
            $fields['placeholder'] = [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'default'    => null,
                'after'      => 'label',
            ];
        }

        if (! $this->db->fieldExists('help_text', 'cms_setting_translations')) {
            $fields['help_text'] = [
                'type'    => 'TEXT',
                'null'    => true,
                'default' => null,
                'after'   => 'placeholder',
            ];
        }

        if ($fields !== []) {
            $this->forge->addColumn('cms_setting_translations', $fields);
        }
    }

    public function down(): void
    {
        $this->db->resetDataCache();

        $fields = array_values(array_filter(
            ['label', 'placeholder', 'help_text'],
            fn (string $field): bool => $this->db->fieldExists($field, 'cms_setting_translations')
        ));

        if ($fields !== []) {
            try {
                $this->forge->dropColumn('cms_setting_translations', $fields);
            } catch (\Throwable) {
                // Ignore when the schema is already partially reverted or the columns were removed earlier.
            }
        }
    }
}
