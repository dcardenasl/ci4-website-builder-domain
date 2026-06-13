<?php

declare(strict_types=1);

namespace App\Libraries\Cms;

class BlockInstanceSerializer
{
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
        $db = \Config\Database::connect();

        $query = $db->table('cms_block_instances i')
            ->select('i.*, b.block_key, b.name as block_type_name')
            ->join('cms_content_blocks b', 'b.id = i.block_id')
            ->where('i.owner_type', $ownerType)
            ->where('i.owner_id', $ownerId)
            ->where('i.is_active', 1)
            ->orderBy('i.sort_order', 'ASC')
            ->get();

        $instances = $query ? $query->getResultArray() : [];

        if (empty($instances)) {
            return [];
        }

        $instanceIds = array_column($instances, 'id');

        $translationsMap = $this->batchResolveBlockTranslations($instanceIds, $langCode, $db);

        $imageFileIds = [];
        foreach ($instances as $instance) {
            if ($instance['block_key'] === 'image') {
                $translationData = $translationsMap[(int) $instance['id']] ?? [];
                $rawData = $translationData['block_data'] ?? null;
                $parsed = is_string($rawData) ? (json_decode($rawData, true) ?? []) : (array) $rawData;
                $fileId = $parsed['file_id'] ?? null;
                if ($fileId !== null) {
                    $imageFileIds[] = (int) $fileId;
                }
            }
        }

        $fileTranslationsMap = !empty($imageFileIds)
            ? $this->batchResolveFileTranslations($imageFileIds, $langCode, $db)
            : [];

        $serialized = [];

        foreach ($instances as $instance) {
            $instanceId = (int) $instance['id'];
            $translation = $translationsMap[$instanceId] ?? [];

            $rawBlockData = $translation['block_data'] ?? null;
            $blockData = is_string($rawBlockData)
                ? (json_decode($rawBlockData, true) ?? [])
                : (array) $rawBlockData;

            $blockConfig = [];
            if (!empty($instance['block_config'])) {
                $blockConfig = is_string($instance['block_config'])
                    ? (json_decode($instance['block_config'], true) ?? [])
                    : (array) $instance['block_config'];
            }

            $blockPayload = [
                'id'           => $instanceId,
                'block_key'    => $instance['block_key'],
                'sort_order'   => (int) $instance['sort_order'],
                'column_index' => isset($instance['column_index']) ? (int) $instance['column_index'] : null,
                'block_config' => $blockConfig,
                'block_data'   => $blockData,
                'is_fallback'  => $translation['is_fallback'] ?? true,
            ];

            if ($instance['block_key'] === 'image') {
                $fileId = $blockData['file_id'] ?? null;
                if ($fileId !== null) {
                    $fileTrans = $fileTranslationsMap[(int) $fileId] ?? [];
                    $blockPayload['block_data']['alt_text']        = $fileTrans['alt_text'] ?? null;
                    $blockPayload['block_data']['caption']         = $fileTrans['caption'] ?? null;
                    $blockPayload['block_data']['title']           = $fileTrans['title'] ?? null;
                    $blockPayload['block_data']['credit']          = $fileTrans['credit'] ?? null;
                    $blockPayload['block_data']['description']     = $fileTrans['description'] ?? null;
                    $blockPayload['block_data']['file_is_fallback'] = $fileTrans['is_fallback'] ?? true;
                }
            }

            $serialized[] = $blockPayload;
        }

        return $serialized;
    }

    /**
     * Batch-resolve block_instance translations for a list of instance IDs.
     * Falls back to the default language when no translation exists for the target.
     *
     * @param  list<int> $instanceIds
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
        $map = [];
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
