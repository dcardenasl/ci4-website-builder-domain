<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migrates nested repeater image fields from legacy `file` declarations to
 * `media_reference` so the admin can render a single robust selector for both
 * internal Hub files and external URLs.
 */
final class MigrateNestedImageFieldsToMediaReference extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('cms_content_blocks')) {
            return;
        }

        $blocks = $this->db->table('cms_content_blocks')
            ->select('id, schema_definition')
            ->get()
            ->getResultArray();

        foreach ($blocks as $block) {
            $blockId = (int) ($block['id'] ?? 0);
            if ($blockId <= 0) {
                continue;
            }

            $schema = $this->decodeJsonArray($block['schema_definition'] ?? null);
            $updated = $this->migrateSchema($schema);

            if ($updated !== $schema) {
                $this->db->table('cms_content_blocks')
                    ->where('id', $blockId)
                    ->update([
                        'schema_definition' => json_encode($updated, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ]);
            }
        }
    }

    public function down(): void
    {
        // Forward-only: the schema move is intentional and reversible only by hand.
    }

    /**
     * @param mixed $value
     * @return array<string, mixed>
     */
    private function decodeJsonArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<string, mixed> $schema
     * @return array<string, mixed>
     */
    private function migrateSchema(array $schema): array
    {
        $schema['fields'] = $this->migrateFieldGroup(is_array($schema['fields'] ?? null) ? (array) $schema['fields'] : []);
        $schema['config_fields'] = $this->migrateFieldGroup(is_array($schema['config_fields'] ?? null) ? (array) $schema['config_fields'] : []);

        return $schema;
    }

    /**
     * @param array<string, mixed> $fields
     * @return array<string, mixed>
     */
    private function migrateFieldGroup(array $fields): array
    {
        foreach ($fields as $fieldKey => $fieldDef) {
            if (! is_array($fieldDef)) {
                continue;
            }

            $type = strtolower((string) ($fieldDef['type'] ?? 'string'));

            if ($type === 'file' && strtolower((string) ($fieldDef['accept'] ?? 'image')) === 'image') {
                $fieldDef['type'] = 'media_reference';
                $fields[$fieldKey] = $fieldDef;
                continue;
            }

            if ($type === 'repeater' && is_array($fieldDef['item_fields'] ?? null)) {
                $fieldDef['item_fields'] = $this->migrateFieldGroup((array) $fieldDef['item_fields']);
                $fields[$fieldKey] = $fieldDef;
                continue;
            }

            if (in_array($type, ['group', 'fieldset'], true) && is_array($fieldDef['fields'] ?? null)) {
                $fieldDef['fields'] = $this->migrateFieldGroup((array) $fieldDef['fields']);
                $fields[$fieldKey] = $fieldDef;
                continue;
            }
        }

        return $fields;
    }
}
