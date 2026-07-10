<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Widens cms_form_fields.field_type from {text,email,phone,textarea} to also
 * support select, radio, checkbox, date, number, url — and adds a nullable
 * `options` column (JSON-encoded list of {value,label} pairs) for the
 * choice-based types (select/radio/checkbox).
 */
class ExpandCmsFormFieldTypesAndOptions extends Migration
{
    private const OLD_TYPES = ['text', 'email', 'phone', 'textarea'];
    private const NEW_TYPES = ['text', 'email', 'phone', 'textarea', 'select', 'radio', 'checkbox', 'date', 'number', 'url'];

    public function up(): void
    {
        $this->forge->modifyColumn('cms_form_fields', [
            'field_type' => [
                'name'       => 'field_type',
                'type'       => 'ENUM',
                'constraint' => self::NEW_TYPES,
                'null'       => false,
                'default'    => 'text',
            ],
        ]);

        $this->forge->addColumn('cms_form_fields', [
            'options' => [
                'type'       => 'TEXT',
                'null'       => true,
                'default'    => null,
                'after'      => 'field_type',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('cms_form_fields', 'options');

        $this->forge->modifyColumn('cms_form_fields', [
            'field_type' => [
                'name'       => 'field_type',
                'type'       => 'ENUM',
                'constraint' => self::OLD_TYPES,
                'null'       => false,
                'default'    => 'text',
            ],
        ]);
    }
}
