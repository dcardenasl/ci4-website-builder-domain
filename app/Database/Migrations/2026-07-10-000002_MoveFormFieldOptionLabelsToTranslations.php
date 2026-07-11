<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Option labels for select/radio/checkbox fields (e.g. "1 Hora") are
 * user-facing content and must be translatable, exactly like a field's own
 * label/placeholder/help_text. They were originally embedded directly in
 * cms_form_fields.options as {value,label} pairs — a single, language-less
 * copy shown identically on every locale.
 *
 * This migration:
 *   1. Adds a translatable `option_labels` JSON column (value -> label map)
 *      to cms_form_field_translations, mirroring how cms_block_instance_
 *      translations.block_data already carries per-language JSON content.
 *   2. Backfills it from each field's existing embedded labels, copied to
 *      every language the field already has a translation row for (so
 *      nothing regresses to a blank/raw value immediately after migrating —
 *      it stays exactly as visible as before until someone translates it).
 *   3. Reduces cms_form_fields.options down to the stable value list only
 *      (e.g. ["1-hora","2-horas"]) — the language-independent structure
 *      that public form submissions actually store.
 */
class MoveFormFieldOptionLabelsToTranslations extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('cms_form_field_translations', [
            'option_labels' => [
                'type'    => 'TEXT',
                'null'    => true,
                'default' => null,
                'after'   => 'help_text',
            ],
        ]);

        if (! $this->db->tableExists('cms_form_fields') || ! $this->db->tableExists('cms_form_field_translations')) {
            return;
        }

        $fields = $this->db->table('cms_form_fields')
            ->select('id, options')
            ->where('options IS NOT NULL', null, false)
            ->get()
            ->getResultArray();

        foreach ($fields as $field) {
            $decoded = json_decode((string) $field['options'], true);
            if (! is_array($decoded) || $decoded === []) {
                continue;
            }

            $labelMap = [];
            $values   = [];
            foreach ($decoded as $option) {
                if (! is_array($option) || ! isset($option['value'])) {
                    continue;
                }
                $value = (string) $option['value'];
                if ($value === '') {
                    continue;
                }
                $values[]          = $value;
                $labelMap[$value]  = isset($option['label']) ? (string) $option['label'] : $value;
            }

            if ($values === []) {
                continue;
            }

            $this->db->table('cms_form_field_translations')
                ->where('form_field_id', (int) $field['id'])
                ->where('option_labels IS NULL', null, false)
                ->update(['option_labels' => json_encode($labelMap, JSON_UNESCAPED_UNICODE)]);

            $this->db->table('cms_form_fields')
                ->where('id', (int) $field['id'])
                ->update(['options' => json_encode(array_values($values), JSON_UNESCAPED_UNICODE)]);
        }
    }

    public function down(): void
    {
        // Forward-only reshape. A rollback could only restore ONE language's
        // labels back into the language-less cms_form_fields.options column
        // (arbitrarily picking one), permanently discarding every other
        // language's translated labels — worse than leaving the schema as-is.
        // Drop the column only; re-run the app against the new shape.
        $this->forge->dropColumn('cms_form_field_translations', 'option_labels');
    }
}
