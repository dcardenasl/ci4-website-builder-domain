<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class AddCollectionListingPresentationConfig extends Migration
{
    public function up(): void
    {
        $block = $this->db->table('cms_content_blocks')
            ->select('id, schema_definition')
            ->where('block_key', 'collection_listing')
            ->get()
            ->getRowArray();

        if (!is_array($block)) {
            return;
        }

        $schema = json_decode((string) ($block['schema_definition'] ?? ''), true);
        $schema = is_array($schema) ? $schema : [];
        $configFields = is_array($schema['config_fields'] ?? null) ? $schema['config_fields'] : [];

        $layout = is_array($configFields['layout_variant'] ?? null) ? $configFields['layout_variant'] : [];
        $options = is_array($layout['options'] ?? null) ? $layout['options'] : ['cards', 'compact', 'portfolio'];
        if (!in_array('list', $options, true)) {
            $options[] = 'list';
        }
        $layout['options'] = $options;
        $configFields['layout_variant'] = $layout;

        foreach ([
            'show_excerpt' => ['Mostrar extracto', true],
            'show_date' => ['Mostrar fecha', true],
            'show_button' => ['Mostrar enlace principal', true],
            'show_item_categories' => ['Mostrar categorías por entrada', true],
            'show_extra_richtext' => ['Mostrar texto adicional', false],
            'show_extra_link' => ['Mostrar enlace adicional', false],
            'show_extra_image' => ['Mostrar imagen adicional', false],
        ] as $key => [$label, $default]) {
            $configFields[$key] = $configFields[$key] ?? [
                'type' => 'boolean',
                'label' => $label,
                'required' => false,
                'default' => $default,
            ];
        }

        $schema['config_fields'] = $configFields;
        $this->db->table('cms_content_blocks')
            ->where('id', (int) $block['id'])
            ->update(['schema_definition' => json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
    }

    public function down(): void
    {
        // The schema is editable CMS configuration. Removing released fields
        // could discard administrator choices, so rollback is intentionally safe.
    }
}
