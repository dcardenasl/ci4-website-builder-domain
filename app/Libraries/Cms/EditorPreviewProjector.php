<?php

declare(strict_types=1);

namespace App\Libraries\Cms;

use App\DTO\Editor\EditorOwnerDTO;
use App\DTO\Editor\EditorPreviewDocumentDTO;
use App\DTO\Editor\EditorPreviewRequestDTO;
use App\Interfaces\Editor\EditorPreviewProjectorInterface;
use CodeIgniter\Database\BaseConnection;
use Config\Editor;
use dcardenasl\Ci4ApiCore\Exceptions\ValidationException;

/**
 * Turns an unsaved editor draft into renderer input.
 *
 * A signed preview link authorizes a document, never its content, so every value
 * here goes through the same validation, sanitization and media resolution the
 * persisting writer applies **in this application**: `BlockDataSanitizer`
 * purifies any string that looks like markup, wherever it sits, which is what
 * `BlockInstanceService` does on save. A preview exists to show what saving
 * would produce, so the two must never clean differently.
 */
final class EditorPreviewProjector implements EditorPreviewProjectorInterface
{
    /** @param BaseConnection<mixed, mixed> $db */
    public function __construct(
        private readonly BaseConnection $db,
        private readonly FileUrlResolver $files,
        private readonly EditorFieldValidator $fields,
        private readonly TranslationFallbackResolver $fallback,
        private readonly Editor $limits,
    ) {
    }

    /** @param array<string, mixed> $payload */
    public function project(EditorOwnerDTO $owner, array $payload): EditorPreviewDocumentDTO
    {
        $request = EditorPreviewRequestDTO::fromArray($payload, $this->limits);
        [$defaultCode, $activeCodes] = $this->locales();
        if (! in_array($request->lang, $activeCodes, true)) {
            throw new ValidationException(lang('Editor.invalidPayload'));
        }

        $catalog = $this->catalog();
        $persisted = $this->ownedInstanceIds($owner);
        $nodes = [];
        $fileIds = [];

        foreach ($request->blocks as $raw) {
            $node = $this->node($raw, $catalog, $persisted, $request->lang, $defaultCode);
            if (isset($nodes[$node['editor_ref']])) {
                throw new ValidationException(lang('Editor.invalidPayload'));
            }
            $schema = $catalog[$node['block_key']];
            $fileIds = array_merge(
                $fileIds,
                $this->files->collectBlockFileIds($node['block_data'], $schema['fields']),
                $this->files->collectSchemaFileIds($node['block_config'], $schema['config_fields']),
            );
            $nodes[$node['editor_ref']] = $node;
        }

        $meta = $fileIds === [] ? [] : $this->files->resolveManyMeta(array_values(array_unique($fileIds)), 'public');
        foreach ($nodes as $ref => $node) {
            $schema = $catalog[$node['block_key']];
            $nodes[$ref]['block_config'] = SchemaMediaMerger::merge($node['block_config'], $schema['config_fields'], $meta, $this->files);
            $nodes[$ref]['block_data'] = SchemaMediaMerger::merge($node['block_data'], $schema['fields'], $meta, $this->files);
        }

        $tree = $this->tree($nodes);
        $fallbackRefs = [];
        foreach ($nodes as $ref => $node) {
            if ($node['is_fallback']) {
                $fallbackRefs[] = (string) $ref;
            }
        }

        return new EditorPreviewDocumentDTO($request->lang, $tree, $request->scopeType, $request->scopeRef, $fallbackRefs);
    }

    /**
     * @param array<string, mixed> $raw
     * @param array<string, array<string, mixed>> $catalog
     * @param list<int> $persisted
     * @return array<string, mixed>
     */
    private function node(array $raw, array $catalog, array $persisted, string $lang, string $defaultCode): array
    {
        $key = $raw['block_key'] ?? null;
        if (! is_string($key) || ! isset($catalog[$key])) {
            throw new ValidationException(lang('Editor.invalidField'));
        }
        $type = $catalog[$key];

        $instanceId = $raw['instance_id'] ?? null;
        $tempId = $raw['temp_id'] ?? null;
        if (is_int($instanceId) && $instanceId > 0) {
            // A draft may only carry blocks of the document the token authorizes.
            if (! in_array($instanceId, $persisted, true)) {
                throw new ValidationException(lang('Editor.invalidPayload'));
            }
            $ref = 'id_' . $instanceId;
        } elseif (is_string($tempId) && preg_match('/^tmp_[a-f0-9-]{36}$/D', $tempId) === 1) {
            $ref = $tempId;
        } else {
            throw new ValidationException(lang('Editor.invalidPayload'));
        }

        $i18n = $this->object($raw['i18n'] ?? []);
        $active = $this->object($i18n[$lang] ?? []);
        $default = $this->object($i18n[$defaultCode] ?? []);
        $this->fields->validate(array_intersect_key($active, $type['fields']), $type['fields']);

        $config = $this->object($raw['config'] ?? []);
        $this->fields->validate(array_intersect_key($config, $type['config_fields']), $type['config_fields']);

        $resolved = $this->fallback->resolve($active, $default, $type['fields'], $lang === $defaultCode);
        $data = BlockDataSanitizer::clean($resolved['block_data']);
        $config = BlockDataSanitizer::clean($config);

        return [
            'editor_ref' => $ref,
            'id' => is_int($instanceId) ? $instanceId : null,
            'block_key' => $key,
            'block_type_name' => $type['name'],
            'block_schema' => $type['schema'],
            'block_config' => SchemaDefaults::applyConfigDefaults($type['schema'], $config),
            'block_data' => SchemaDefaults::apply($data, $type['fields']),
            'parent_ref' => $this->parentRef($raw),
            'sort_order' => is_int($raw['sort_order'] ?? null) ? $raw['sort_order'] : 0,
            'is_fallback' => $resolved['is_fallback'],
            'fallback_fields' => $resolved['fallback_fields'],
            'children' => [],
        ];
    }

    /** @param array<string, mixed> $raw */
    private function parentRef(array $raw): ?string
    {
        $parentId = $raw['parent_instance_id'] ?? null;
        $parentTemp = $raw['parent_temp_id'] ?? null;
        if (is_int($parentId) && $parentId > 0) {
            return 'id_' . $parentId;
        }

        return is_string($parentTemp) && $parentTemp !== '' ? $parentTemp : null;
    }

    /**
     * Nest by parent reference. Orphans render at the root instead of vanishing:
     * a preview that silently drops a block would hide the editor's own mistake.
     *
     * @param array<string, array<string, mixed>> $nodes
     * @return list<array<string, mixed>>
     */
    private function tree(array $nodes): array
    {
        uasort($nodes, static fn (array $a, array $b): int => [$a['sort_order'], (string) $a['editor_ref']] <=> [$b['sort_order'], (string) $b['editor_ref']]);
        $roots = [];
        $byRef = [];
        foreach ($nodes as $ref => $node) {
            $byRef[$ref] = $node;
        }
        foreach ($byRef as $ref => $node) {
            $parent = $node['parent_ref'];
            if ($parent !== null && isset($byRef[$parent]) && $parent !== $ref) {
                continue;
            }
            $roots[] = $this->attach($ref, $byRef, [$ref => true]);
        }

        return $roots;
    }

    /**
     * @param array<string, array<string, mixed>> $byRef
     * @param array<string, bool> $seen
     * @return array<string, mixed>
     */
    private function attach(string $ref, array $byRef, array $seen): array
    {
        $node = $byRef[$ref];
        foreach ($byRef as $childRef => $child) {
            if ($child['parent_ref'] === $ref && ! isset($seen[$childRef])) {
                $seen[$childRef] = true;
                $node['children'][] = $this->attach((string) $childRef, $byRef, $seen);
            }
        }

        return $node;
    }

    /** @return array{0: string, 1: list<string>} */
    private function locales(): array
    {
        $rows = $this->db->table('cms_languages')->where('is_active', 1)->orderBy('id')->get();
        $default = '';
        $codes = [];
        foreach ($rows === false ? [] : $rows->getResultArray() as $row) {
            $codes[] = (string) $row['code'];
            if ((bool) $row['is_default']) {
                $default = (string) $row['code'];
            }
        }

        return [$default, $codes];
    }

    /** @return array<string, array<string, mixed>> */
    private function catalog(): array
    {
        $rows = $this->db->table('cms_content_blocks')->where('is_active', 1)->orderBy('id')->get();
        $catalog = [];
        foreach ($rows === false ? [] : $rows->getResultArray() as $row) {
            $schema = json_decode((string) ($row['schema_definition'] ?? '{}'), true);
            $schema = is_array($schema) ? $schema : [];
            $catalog[(string) $row['block_key']] = [
                'name' => (string) $row['name'],
                'schema' => $schema,
                'fields' => is_array($schema['fields'] ?? null) ? $schema['fields'] : [],
                'config_fields' => is_array($schema['config_fields'] ?? null) ? $schema['config_fields'] : [],
            ];
        }

        return $catalog;
    }

    /** @return list<int> */
    private function ownedInstanceIds(EditorOwnerDTO $owner): array
    {
        $rows = $this->db->table('cms_block_instances')
            ->select('id')->where('owner_type', $owner->type)->where('owner_id', $owner->id)->get();

        return array_values(array_map(static fn (array $row): int => (int) $row['id'], $rows === false ? [] : $rows->getResultArray()));
    }

    /** @return array<string, mixed> */
    private function object(mixed $value): array
    {
        if (! is_array($value) || ($value !== [] && array_is_list($value))) {
            throw new ValidationException(lang('Editor.invalidPayload'));
        }

        return $value;
    }
}
