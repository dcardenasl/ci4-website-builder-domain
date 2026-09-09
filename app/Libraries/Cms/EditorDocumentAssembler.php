<?php

declare(strict_types=1);

namespace App\Libraries\Cms;

use App\DTO\Editor\EditorDocumentResponseDTO;
use App\DTO\Editor\EditorOwnerDTO;
use dcardenasl\Ci4ApiCore\Support\JsonCastNormalizer;

/** Projects one consistent snapshot; never substitutes display fallback for editable values. */
final class EditorDocumentAssembler
{
    public function __construct(private readonly FileUrlResolver $files)
    {
    }

    public function assemble(EditorOwnerDTO $owner, EditorDocumentSnapshot $snapshot): EditorDocumentResponseDTO
    {
        $locales = [];
        $codes = [];
        $defaultCode = '';
        foreach ($snapshot->languages as $row) {
            if (! (bool) $row['is_active']) {
                continue;
            }
            $code = (string) $row['code'];
            $codes[(int) $row['id']] = $code;
            $locales[] = ['id' => (int) $row['id'], 'code' => $code, 'name' => (string) $row['name'], 'is_default' => (bool) $row['is_default']];
            if ((bool) $row['is_default']) {
                $defaultCode = $code;
            }
        }

        $ownerI18n = [];
        foreach ($snapshot->ownerTranslations as $row) {
            $code = $codes[(int) $row['language_id']] ?? null;
            if ($code !== null) {
                $ownerI18n[$code] = $row;
            }
        }
        $ownerData = $owner->toArray() + [
            'title' => (string) ($ownerI18n[$defaultCode]['title'] ?? ''),
            'status' => (string) ($snapshot->owner['status'] ?? $snapshot->owner['workflow_status'] ?? 'draft'),
            'collection_id' => isset($snapshot->owner['collection_id']) ? (int) $snapshot->owner['collection_id'] : null,
            'i18n' => $ownerI18n,
        ];
        $template = JsonCastNormalizer::toArray($snapshot->collection['block_template'] ?? null);
        $templateBlocks = is_array($template['blocks'] ?? null) ? $template['blocks'] : [];
        $flags = [];
        foreach ($templateBlocks as $block) {
            if (is_array($block) && is_string($block['block_key'] ?? null)) {
                $flags[$block['block_key']] = ['locked' => (bool) ($block['locked'] ?? false), 'required' => (bool) ($block['required'] ?? false)];
            }
        }

        $types = [];
        $catalog = [];
        foreach ($snapshot->catalog as $row) {
            $types[(int) $row['id']] = $row;
            if (! (bool) $row['is_active'] || ! (bool) $row[$owner->type === 'page' ? 'supports_pages' : 'supports_entries']) {
                continue;
            }
            $schema = JsonCastNormalizer::toArray($row['schema_definition']);
            $catalog[] = [
                'block_id' => (int) $row['id'], 'block_key' => (string) $row['block_key'],
                'name' => (string) $row['name'], 'icon' => (string) ($row['icon'] ?? ''), 'category' => (string) $row['category'],
                'supports_pages' => (bool) $row['supports_pages'], 'supports_entries' => (bool) $row['supports_entries'],
                'is_container' => (bool) $row['is_container'], 'allowed_children' => $schema['allowed_children'] ?? [],
                'fields' => $schema['fields'] ?? [], 'config_fields' => $schema['config_fields'] ?? [],
                'capabilities' => [
                    'content' => (new BlockSchemaIntrospector())->introspect($schema),
                    'config' => (new BlockSchemaIntrospector())->introspect(['fields' => $schema['config_fields'] ?? []]),
                ],
            ];
        }
        $translations = [];
        foreach ($snapshot->translations as $row) {
            $code = $codes[(int) $row['language_id']] ?? null;
            if ($code !== null) {
                $translations[(int) $row['instance_id']][$code] = JsonCastNormalizer::toArray($row['block_data']);
            }
        }
        $emptyI18n = array_fill_keys(array_values($codes), []);
        $blocks = [];
        $fileIds = [];
        foreach ($snapshot->instances as $row) {
            $id = (int) $row['id'];
            $type = $types[(int) $row['block_id']] ?? null;
            if ($type === null) {
                throw new \UnexpectedValueException('Editor block type is missing.');
            }
            $key = (string) $type['block_key'];
            $schema = JsonCastNormalizer::toArray($type['schema_definition']);
            $config = JsonCastNormalizer::toArray($row['block_config']);
            $configFields = is_array($schema['config_fields'] ?? null) ? $schema['config_fields'] : [];
            $fields = is_array($schema['fields'] ?? null) ? $schema['fields'] : [];
            $fileIds = array_merge($fileIds, $this->files->collectSchemaFileIds($config, $configFields));
            foreach ($translations[$id] ?? [] as $raw) {
                $fileIds = array_merge($fileIds, $this->files->collectBlockFileIds($raw, $fields));
            }
            $blocks[] = [
                'instance_id' => $id, 'temp_id' => null, 'block_id' => (int) $row['block_id'], 'block_key' => $key,
                'parent_instance_id' => isset($row['parent_instance_id']) ? (int) $row['parent_instance_id'] : null,
                'sort_order' => (int) $row['sort_order'], 'column_index' => isset($row['column_index']) ? (int) $row['column_index'] : null,
                'is_active' => (bool) $row['is_active'],
                'config' => $config,
                'i18n' => array_replace($emptyI18n, $translations[$id] ?? []),
                'locked' => $flags[$key]['locked'] ?? false, 'required' => $flags[$key]['required'] ?? false,
            ];
        }
        usort($blocks, static fn (array $a, array $b): int => [$a['sort_order'], $a['instance_id']] <=> [$b['sort_order'], $b['instance_id']]);

        return new EditorDocumentResponseDTO($ownerData, $snapshot->version(), $locales, $blocks, $catalog, $template, $this->files->resolveManyMeta(array_values(array_unique($fileIds))));
    }
}
