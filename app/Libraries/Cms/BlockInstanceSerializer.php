<?php

declare(strict_types=1);

namespace App\Libraries\Cms;

class BlockInstanceSerializer
{
    private FileUrlResolver $fileUrlResolver;

    public function __construct(?FileUrlResolver $fileUrlResolver = null)
    {
        $this->fileUrlResolver = $fileUrlResolver ?? service('fileUrlResolver');
    }

    /**
     * Resolve and serialize all block instances for a given owner.
     *
     * Uses a single batch query per translation table to avoid N+1 queries.
     *
     * @param string $ownerType Type of owner ('page', 'entry')
     * @param int    $ownerId   ID of the owner
     * @param string $langCode  Target language code
     * @return array<int, array<string, mixed>>
     */
    public function forContent(string $ownerType, int $ownerId, string $langCode): array
    {
        $blocksByOwner = $this->forOwnersBatch($ownerType, [$ownerId], $langCode);

        return $blocksByOwner[$ownerId] ?? [];
    }

    /**
     * Resolve and serialize block instances for multiple owners without a query
     * per owner. The result deliberately remains grouped by owner ID so callers
     * can enrich a listing without exposing this loading detail to consumers.
     *
     * @param string    $ownerType Type of owner ('page', 'entry')
     * @param list<int> $ownerIds  IDs of the owners
     * @param string    $langCode  Target language code
     * @return array<int, list<array<string, mixed>>> Top-level blocks by owner ID
     */
    public function forOwnersBatch(string $ownerType, array $ownerIds, string $langCode): array
    {
        $ownerIds = array_values(array_unique(array_filter(
            array_map(static fn (mixed $ownerId): int => (int) $ownerId, $ownerIds),
            static fn (int $ownerId): bool => $ownerId > 0
        )));

        if ($ownerIds === []) {
            return [];
        }

        $db = \Config\Database::connect();

        $query = $db->table('cms_block_instances i')
            ->select('i.*, b.block_key, b.name as block_type_name, b.schema_definition')
            ->join('cms_content_blocks b', 'b.id = i.block_id')
            ->where('i.owner_type', $ownerType)
            ->whereIn('i.owner_id', $ownerIds)
            ->where('i.is_active', 1)
            ->orderBy('i.sort_order', 'ASC')
            ->get();

        $instances = $query ? $query->getResultArray() : [];

        if (empty($instances)) {
            return [];
        }

        $instanceIds = array_column($instances, 'id');

        $translationsMap = $this->batchResolveBlockTranslations($instanceIds, $langCode, $db);

        // Collect all file IDs in a single pre-pass via schema field declarations
        $allFileIds = [];
        foreach ($instances as $instance) {
            $translationData = $translationsMap[(int) $instance['id']] ?? [];
            $rawData         = $translationData['block_data'] ?? null;
            $blockData       = is_string($rawData) ? (json_decode($rawData, true) ?? []) : (array) $rawData;

            $schemaDefinition = $this->parseSchemaDefinition((string) ($instance['schema_definition'] ?? ''));
            $schemaFields = (array) ($schemaDefinition['fields'] ?? []);
            $schemaConfigFields = (array) ($schemaDefinition['config_fields'] ?? []);

            $allFileIds = array_merge($allFileIds, $this->fileUrlResolver->collectBlockFileIds($blockData, $schemaFields));

            $blockConfig = [];
            if (!empty($instance['block_config'])) {
                $blockConfig = is_string($instance['block_config'])
                    ? (json_decode($instance['block_config'], true) ?? [])
                    : (array) $instance['block_config'];
            }
            $allFileIds = array_merge($allFileIds, $this->fileUrlResolver->collectSchemaFileIds($blockConfig, $schemaConfigFields));
        }

        $allFileIds        = array_values(array_unique($allFileIds));
        $fileUrlMap        = !empty($allFileIds)
            ? $this->fileUrlResolver->resolveMany($allFileIds, 'public')
            : [];
        $fileTranslationsMap = !empty($allFileIds)
            ? $this->batchResolveFileTranslations($allFileIds, $langCode, $db)
            : [];

        // Serialize ALL instances (top-level and children alike) into a map keyed by id
        $serializedMap = [];
        $ownerByInstanceId = [];

        foreach ($instances as $instance) {
            $instanceId  = (int) $instance['id'];
            $translation = $translationsMap[$instanceId] ?? [];

            $rawBlockData = $translation['block_data'] ?? null;
            $blockData    = is_string($rawBlockData)
                ? (json_decode($rawBlockData, true) ?? [])
                : (array) $rawBlockData;
            $blockData = BlockTextPayload::normalize($blockData);

            $blockConfig = [];
            if (!empty($instance['block_config'])) {
                $blockConfig = is_string($instance['block_config'])
                    ? (json_decode($instance['block_config'], true) ?? [])
                    : (array) $instance['block_config'];
            }

            $schemaDefinition = $this->parseSchemaDefinition((string) ($instance['schema_definition'] ?? ''));
            $schemaFields = (array) ($schemaDefinition['fields'] ?? []);
            $schemaConfigFields = (array) ($schemaDefinition['config_fields'] ?? []);

            $blockConfig = SchemaDefaults::applyConfigDefaults($schemaDefinition, $blockConfig);
            if ($schemaConfigFields !== []) {
                $blockConfig = $this->fileUrlResolver->normalizeBlockConfig($blockConfig, $schemaConfigFields);
            }
            $blockData = SchemaDefaults::apply($blockData, $schemaFields);

            $blockPayload = [
                'id'                 => $instanceId,
                'block_key'          => $instance['block_key'],
                'sort_order'         => (int) $instance['sort_order'],
                'column_index'       => isset($instance['column_index']) ? (int) $instance['column_index'] : null,
                'parent_instance_id' => isset($instance['parent_instance_id']) ? (int) $instance['parent_instance_id'] : null,
                'block_config'       => $blockConfig,
                'block_data'         => $blockData,
                'is_fallback'        => $translation['is_fallback'] ?? true,
                'children'           => [],
            ];

            // Resolve file-type fields and expand file IDs inside repeater items
            $blockPayload['block_data'] = $this->mergeFileMetadata(
                $blockPayload['block_data'],
                $schemaFields,
                $fileTranslationsMap,
                $fileUrlMap
            );

            $serializedMap[$instanceId] = $blockPayload;
            $ownerByInstanceId[$instanceId] = (int) $instance['owner_id'];
        }

        // Build tree: attach children to their parent's 'children' array, return only top-level
        $childrenByParent = [];
        foreach ($serializedMap as $instanceId => $block) {
            $parentId = $block['parent_instance_id'];
            if ($parentId !== null) {
                $childrenByParent[$parentId][] = $instanceId;
            }
        }

        $topLevelByOwner = array_fill_keys($ownerIds, []);
        foreach ($serializedMap as $instanceId => $block) {
            if ($block['parent_instance_id'] === null) {
                $block['children'] = $this->buildChildren($instanceId, $serializedMap, $childrenByParent);
                $ownerId = $ownerByInstanceId[$instanceId] ?? 0;
                if (isset($topLevelByOwner[$ownerId])) {
                    $topLevelByOwner[$ownerId][] = $block;
                }
            }
        }

        foreach ($topLevelByOwner as &$blocks) {
            usort($blocks, static fn (array $a, array $b): int => $a['sort_order'] <=> $b['sort_order']);
        }
        unset($blocks);

        return $topLevelByOwner;
    }

    // ─── Private helpers ────────────────────────────────────────────────────────

    /**
     * Recursively build the children array for a given parent instance.
     *
     * @param  int                                   $parentId
     * @param  array<int, array<string, mixed>>      $serializedMap
     * @param  array<int, list<int>>                 $childrenByParent
     * @return list<array<string, mixed>>
     */
    private function buildChildren(int $parentId, array $serializedMap, array $childrenByParent): array
    {
        $childIds = $childrenByParent[$parentId] ?? [];
        if (empty($childIds)) {
            return [];
        }

        $children = [];
        foreach ($childIds as $childId) {
            if (!isset($serializedMap[$childId])) {
                continue;
            }
            $child             = $serializedMap[$childId];
            $child['children'] = $this->buildChildren($childId, $serializedMap, $childrenByParent);
            $children[]        = $child;
        }

        usort($children, static fn (array $a, array $b): int => $a['sort_order'] <=> $b['sort_order']);

        return $children;
    }

    /**
     * Parse the schema_definition JSON string and return only the 'fields' map.
     *
     * @return array<string, array<string, mixed>>
     */
    private function parseSchemaDefinition(string $schemaDef): array
    {
        if ($schemaDef === '') {
            return [];
        }
        $schema = json_decode($schemaDef, true);
        if (!is_array($schema)) {
            return [];
        }

        return $schema;
    }

    /**
     * Merge resolved file metadata into block_data for all file-type and repeater fields.
     *
     * For a 'file' field named "image":
     *   - Reads  block_data["image_file_id"]
     *   - Writes block_data["image_alt_text"], ["image_caption"], ["image_title"], ["image_credit"]
     *
     * For a 'repeater' field, the same enrichment is applied inside each item.
     *
     * @param  array<string, mixed>        $blockData
     * @param  array<string, array<string, mixed>> $schemaFields
     * @param  array<int, array<string, mixed>>    $fileTransMap   keyed by file_id
     * @param  array<int, string>                 $fileUrlMap      keyed by file_id
     * @return array<string, mixed>
     */
    private function mergeFileMetadata(array $blockData, array $schemaFields, array $fileTransMap, array $fileUrlMap): array
    {
        foreach ($schemaFields as $fieldKey => $fieldDef) {
            $type = $fieldDef['type'] ?? 'string';

            if ($type === 'file') {
                $this->mergeSingleFileField(
                    $blockData,
                    $fieldKey,
                    $fileTransMap,
                    $fileUrlMap
                );
            } elseif ($type === 'media_reference') {
                $this->mergeMediaReferenceField($blockData, $fieldKey);
            } elseif ($type === 'repeater') {
                $items      = $blockData[$fieldKey] ?? [];
                $itemFields = $fieldDef['item_fields'] ?? [];
                if (!is_array($items) || !is_array($itemFields)) {
                    continue;
                }

                $enriched = [];
                foreach ($items as $item) {
                    if (!is_array($item)) {
                        $enriched[] = $item;
                        continue;
                    }
                    $enriched[] = $this->mergeFileMetadata($item, $itemFields, $fileTransMap, $fileUrlMap);
                }
                $blockData[$fieldKey] = $enriched;
            } elseif (in_array($type, ['group', 'fieldset'], true)) {
                $nestedFields = $fieldDef['fields'] ?? [];
                $nestedData   = $blockData[$fieldKey] ?? [];
                if (is_array($nestedData) && is_array($nestedFields)) {
                    $blockData[$fieldKey] = $this->mergeFileMetadata($nestedData, $nestedFields, $fileTransMap, $fileUrlMap);
                }
            }
        }

        return $blockData;
    }

    /**
     * Normalize a single file field in block_data.
     *
     * @param array<string, mixed> $blockData
     * @param array<int, array<string, mixed>> $fileTransMap
     * @param array<int, string> $fileUrlMap
     */
    private function mergeSingleFileField(array &$blockData, string $fieldKey, array $fileTransMap, array $fileUrlMap): void
    {
        $fileIdKey = $fieldKey . '_file_id';
        $urlKey    = $fieldKey . '_url';
        $fileId    = $blockData[$fileIdKey] ?? null;

        $resolvedFileId = $this->fileUrlResolver->resolveFileIdFromValue(
            $fileId,
            isset($blockData[$urlKey]) ? (string) $blockData[$urlKey] : null
        );

        if ($resolvedFileId !== null) {
            $fileTrans = $fileTransMap[$resolvedFileId] ?? [];
            $blockData[$fieldKey . '_alt_text'] = $fileTrans['alt_text'] ?? null;
            $blockData[$fieldKey . '_caption']  = $fileTrans['caption'] ?? null;
            $blockData[$fieldKey . '_title']    = $fileTrans['title'] ?? null;
            $blockData[$fieldKey . '_credit']   = $fileTrans['credit'] ?? null;
            $blockData[$urlKey] = $fileUrlMap[$resolvedFileId] ?? $this->fileUrlResolver->resolve($resolvedFileId, 'public');

            return;
        }

        $blockData[$urlKey] = $this->fileUrlResolver->resolveUrlValue(
            $fileId,
            isset($blockData[$urlKey]) ? (string) $blockData[$urlKey] : null
        );
    }

    /**
     * Normalize a media_reference field into the canonical nested payload.
     *
     * @param array<string, mixed> $blockData
     */
    private function mergeMediaReferenceField(array &$blockData, string $fieldKey): void
    {
        $reference = is_array($blockData[$fieldKey] ?? null) ? $blockData[$fieldKey] : [];

        $normalized = $this->fileUrlResolver->normalizeMediaReference($reference);

        $blockData[$fieldKey] = $normalized;
    }

    /**
     * Batch-resolve block_instance translations for a list of instance IDs.
     * Falls back to the default language when no translation exists for the target.
     *
     * @param  list<int> $instanceIds
     * @param  string    $langCode
     * @param  object    $db
     * @return array<int, array<string, mixed>>     keyed by instance_id
     */
    private function batchResolveBlockTranslations(
        array $instanceIds,
        string $langCode,
        object $db
    ): array {
        [$langId, $defaultLangId] = $this->resolveLanguageIds($langCode, $db);

        $langIds = array_unique(array_filter([$langId, $defaultLangId]));

        if (empty($langIds) || empty($instanceIds)) {
            return [];
        }

        $result = $db->table('cms_block_instance_translations')
            ->whereIn('instance_id', $instanceIds)
            ->whereIn('language_id', $langIds)
            ->get();
        $rows = $result ? $result->getResultArray() : [];

        $map = [];
        foreach ($rows as $row) {
            $iid = (int) $row['instance_id'];
            $lid = (int) $row['language_id'];
            if (!isset($map[$iid]) || $lid === $langId) {
                $map[$iid] = [
                    'block_data'  => $row['block_data'],
                    'is_fallback' => $lid !== $langId,
                ];
            }
        }

        return $map;
    }

    /**
     * Batch-resolve file translations for a list of file IDs.
     *
     * @param  list<int> $fileIds
     * @param  string    $langCode
     * @param  object    $db
     * @return array<int, array<string, mixed>>     keyed by file_id
     */
    private function batchResolveFileTranslations(
        array $fileIds,
        string $langCode,
        object $db
    ): array {
        [$langId, $defaultLangId] = $this->resolveLanguageIds($langCode, $db);

        $langIds = array_unique(array_filter([$langId, $defaultLangId]));

        if (empty($langIds) || empty($fileIds)) {
            return [];
        }

        $result = $db->table('cms_file_translations')
            ->whereIn('file_id', $fileIds)
            ->whereIn('language_id', $langIds)
            ->get();
        $rows = $result ? $result->getResultArray() : [];

        $fields = ['alt_text', 'caption', 'title', 'credit', 'description'];
        $map    = [];
        foreach ($rows as $row) {
            $fid = (int) $row['file_id'];
            $lid = (int) $row['language_id'];
            if (!isset($map[$fid]) || $lid === $langId) {
                $entry = ['is_fallback' => $lid !== $langId];
                foreach ($fields as $f) {
                    $entry[$f] = $row[$f] ?? null;
                }
                $map[$fid] = $entry;
            }
        }

        return $map;
    }

    /**
     * Returns [targetLangId, defaultLangId].
     * Queries the DB once; results should be cached at the connection layer.
     *
     * @return array{0: int|null, 1: int|null}
     */
    private function resolveLanguageIds(string $langCode, object $db): array
    {
        $result = $db->table('cms_languages')
            ->whereIn('code', [$langCode])
            ->orWhere('is_default', 1)
            ->where('is_active', 1)
            ->get();
        $rows = $result ? $result->getResultArray() : [];

        $targetId  = null;
        $defaultId = null;

        foreach ($rows as $row) {
            if ($row['code'] === $langCode) {
                $targetId = (int) $row['id'];
            }
            if ((int) $row['is_default'] === 1) {
                $defaultId = (int) $row['id'];
            }
        }

        return [$targetId ?? $defaultId, $defaultId];
    }
}
