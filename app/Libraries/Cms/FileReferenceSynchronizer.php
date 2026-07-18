<?php

declare(strict_types=1);

namespace App\Libraries\Cms;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

/**
 * Rebuilds CMS file references from canonical resource data.
 *
 * This keeps `cms_file_references` aligned with the actual CMS records and makes
 * the admin "used in" view, delete guards, and cleanup jobs deterministic.
 */
class FileReferenceSynchronizer
{
    /** @var BaseConnection<mixed, mixed> */
    private BaseConnection $db;

    private FileUrlResolver $urlResolver;

    /** @var array<int, string>|null */
    private ?array $languageCodeById = null;

    /**
     * @param BaseConnection<mixed, mixed>|null $db
     */
    public function __construct(FileUrlResolver $urlResolver, ?BaseConnection $db = null)
    {
        $this->urlResolver = $urlResolver;
        $this->db = $db ?? Database::connect();
    }

    public function syncEntry(int $entryId): void
    {
        $references = [];

        $result = $this->db->table('cms_entry_translations')
            ->where('entry_id', $entryId)
            ->get();
        $translations = $result ? $result->getResultArray() : [];

        foreach ($translations as $translation) {
            $languageCode = $this->languageCode((int) ($translation['language_id'] ?? 0));
            $labelPrefix  = $this->entryLabel((int) $entryId, $translation, $languageCode);

            $featuredFileId = $this->urlResolver->resolveFileIdFromValue(
                $translation['featured_file_id'] ?? null,
                isset($translation['featured_image_url']) ? (string) $translation['featured_image_url'] : null
            );
            if ($featuredFileId !== null) {
                $references[] = $this->referenceRow(
                    $featuredFileId,
                    'entry',
                    $entryId,
                    $this->buildRole('featured_image', $languageCode),
                    $labelPrefix . ' - Featured image'
                );
            }

            $ogFileId = $this->urlResolver->resolveFileIdFromValue(
                $translation['og_image_file_id'] ?? null,
                isset($translation['og_image_url']) ? (string) $translation['og_image_url'] : null
            );
            if ($ogFileId !== null) {
                $references[] = $this->referenceRow(
                    $ogFileId,
                    'entry',
                    $entryId,
                    $this->buildRole('og_image', $languageCode),
                    $labelPrefix . ' - OG image'
                );
            }
        }

        $this->replaceReferences('entry', $entryId, $references);
        $this->syncOwnedBlockInstances('entry', $entryId);
    }

    public function syncPage(int $pageId): void
    {
        $references = [];

        $result = $this->db->table('cms_page_translations')
            ->where('page_id', $pageId)
            ->get();
        $translations = $result ? $result->getResultArray() : [];

        foreach ($translations as $translation) {
            $languageCode = $this->languageCode((int) ($translation['language_id'] ?? 0));
            $labelPrefix  = $this->pageLabel((int) $pageId, $translation, $languageCode);

            $ogFileId = $this->urlResolver->resolveFileIdFromValue(
                $translation['og_image_file_id'] ?? null,
                isset($translation['og_image_url']) ? (string) $translation['og_image_url'] : null
            );
            if ($ogFileId !== null) {
                $references[] = $this->referenceRow(
                    $ogFileId,
                    'page',
                    $pageId,
                    $this->buildRole('og_image', $languageCode),
                    $labelPrefix . ' - OG image'
                );
            }
        }

        $this->replaceReferences('page', $pageId, $references);
        $this->syncOwnedBlockInstances('page', $pageId);
    }

    public function syncSetting(int $settingId): void
    {
        $result = $this->db->table('cms_settings')
            ->select('id, setting_key, setting_type, setting_value, is_translatable')
            ->where('id', $settingId)
            ->get();
        $setting = $result ? $result->getRowArray() : null;

        $references = [];
        if (is_array($setting) && ($setting['setting_type'] ?? null) === 'file_id') {
            $fileId = is_numeric($setting['setting_value'] ?? null) ? (int) $setting['setting_value'] : 0;
            if ($fileId > 0) {
                $references[] = $this->referenceRow(
                    $fileId,
                    'setting',
                    $settingId,
                    'setting_value',
                    trim((string) ($setting['setting_key'] ?? '')) ?: 'Setting #' . $settingId
                );
            }

            if ((int) ($setting['is_translatable'] ?? 0) === 1) {
                $translationResult = $this->db->table('cms_setting_translations')
                    ->select('language_id, setting_value')
                    ->where('setting_id', $settingId)
                    ->get();

                foreach ($translationResult ? $translationResult->getResultArray() : [] as $translation) {
                    $translatedFileId = is_numeric($translation['setting_value'] ?? null)
                        ? (int) $translation['setting_value']
                        : 0;
                    if ($translatedFileId <= 0) {
                        continue;
                    }

                    $languageCode = $this->languageCode((int) ($translation['language_id'] ?? 0));
                    $references[] = $this->referenceRow(
                        $translatedFileId,
                        'setting',
                        $settingId,
                        $this->buildRole('setting_value', $languageCode),
                        (trim((string) ($setting['setting_key'] ?? '')) ?: 'Setting #' . $settingId)
                            . ' (' . $languageCode . ')'
                    );
                }
            }
        }

        $this->replaceReferences('setting', $settingId, $references);
    }

    public function removeResourceReferences(string $resourceType, int $resourceId): void
    {
        if (! in_array($resourceType, ['entry', 'page', 'setting', 'block_instance'], true)) {
            throw new \InvalidArgumentException(lang('Cms.file_references.unsupported_resource', [$resourceType]));
        }

        $this->replaceReferences($resourceType, $resourceId, []);
    }

    public function syncBlockInstance(int $instanceId): void
    {
        $result = $this->db->table('cms_block_instances i')
            ->select('i.id, i.block_id, i.owner_type, i.owner_id, i.block_config, b.block_key, b.name as block_name, b.schema_definition')
            ->join('cms_content_blocks b', 'b.id = i.block_id')
            ->where('i.id', $instanceId)
            ->get();
        $instance = $result ? $result->getRowArray() : null;

        if (! is_array($instance) || $instance === []) {
            return;
        }

        $schemaFields = $this->schemaFields((string) ($instance['schema_definition'] ?? ''));
        $schemaConfigFields = $this->schemaConfigFields((string) ($instance['schema_definition'] ?? ''));
        if ($schemaFields === [] && $schemaConfigFields === []) {
            $this->replaceReferences('block_instance', $instanceId, []);
            return;
        }

        $result = $this->db->table('cms_block_instance_translations')
            ->where('instance_id', $instanceId)
            ->get();
        $translations = $result ? $result->getResultArray() : [];

        $references = [];
        $blockLabel  = $this->blockLabel($instance);

        $blockConfig = $this->decodeJsonArray($instance['block_config'] ?? null);

        foreach ($translations as $translation) {
            $languageCode = $this->languageCode((int) ($translation['language_id'] ?? 0));
            $blockData    = $this->decodeJsonArray($translation['block_data'] ?? null);
            if ($blockData === []) {
                continue;
            }

            $references = array_merge(
                $references,
                $this->collectBlockReferences($blockData, $schemaFields, $instanceId, $languageCode, $blockLabel)
            );

        }

        if ($schemaConfigFields !== [] && $blockConfig !== []) {
            $references = array_merge(
                $references,
                $this->collectBlockReferences(
                    $blockConfig,
                    $schemaConfigFields,
                    $instanceId,
                    '',
                    $blockLabel,
                    'config'
                )
            );
        }

        $this->replaceReferences('block_instance', $instanceId, $references);
    }

    /**
     * Rebuild every CMS file reference from scratch.
     *
     * @return array{pages: int, entries: int, settings: int, block_instances: int, references: int}
     */
    public function rebuildAll(): array
    {
        $counts = [
            'pages' => 0,
            'entries' => 0,
            'settings' => 0,
            'block_instances' => 0,
            'references' => 0,
        ];

        $pagesResult = $this->db->table('cms_pages')->select('id')->get();
        foreach ($pagesResult ? $pagesResult->getResultArray() : [] as $row) {
            $pageId = (int) ($row['id'] ?? 0);
            if ($pageId <= 0) {
                continue;
            }
            $this->syncPage($pageId);
            $counts['pages']++;
        }

        $entriesResult = $this->db->table('cms_entries')->select('id')->get();
        foreach ($entriesResult ? $entriesResult->getResultArray() : [] as $row) {
            $entryId = (int) ($row['id'] ?? 0);
            if ($entryId <= 0) {
                continue;
            }
            $this->syncEntry($entryId);
            $counts['entries']++;
        }

        $settingsResult = $this->db->table('cms_settings')->select('id')->get();
        foreach ($settingsResult ? $settingsResult->getResultArray() : [] as $row) {
            $settingId = (int) ($row['id'] ?? 0);
            if ($settingId <= 0) {
                continue;
            }
            $this->syncSetting($settingId);
            $counts['settings']++;
        }

        $blockInstancesResult = $this->db->table('cms_block_instances')->select('id')->get();
        foreach ($blockInstancesResult ? $blockInstancesResult->getResultArray() : [] as $row) {
            $instanceId = (int) ($row['id'] ?? 0);
            if ($instanceId <= 0) {
                continue;
            }
            $this->syncBlockInstance($instanceId);
            $counts['block_instances']++;
        }

        $counts['references'] = $this->db->tableExists('cms_file_references')
            ? (int) $this->db->table('cms_file_references')->countAllResults()
            : 0;

        return $counts;
    }

    /**
     * @param array<string, mixed> $blockData
     * @param array<string, array<string, mixed>> $schemaFields
     * @return list<array{hub_file_id:int, resource_type:string, resource_id:int, block_instance_id:int|null, role:string, label:?string, created_at:string}>
     */
    private function collectBlockReferences(
        array $blockData,
        array $schemaFields,
        int $instanceId,
        string $languageCode,
        string $blockLabel,
        string $pathPrefix = ''
    ): array {
        $references = [];

        foreach ($schemaFields as $fieldKey => $fieldDef) {
            $type = strtolower((string) ($fieldDef['type'] ?? 'string'));
            $fieldPath = $pathPrefix === '' ? $fieldKey : $pathPrefix . '.' . $fieldKey;

            if ($type === 'media_reference') {
                $fileId = $this->urlResolver->resolveMediaReferenceFileId($blockData[$fieldKey] ?? null);
                if ($fileId === null) {
                    continue;
                }

                $references[] = $this->referenceRow(
                    $fileId,
                    'block_instance',
                    $instanceId,
                    $this->buildRole($fieldPath, $languageCode),
                    $blockLabel . ' - ' . $this->humanizeFieldLabel((string) ($fieldDef['label'] ?? $fieldKey), $fieldPath)
                );
                continue;
            }

            if ($type === 'repeater') {
                $items = $blockData[$fieldKey] ?? [];
                if (! is_array($items)) {
                    continue;
                }

                $itemFields = is_array($fieldDef['item_fields'] ?? null) ? (array) $fieldDef['item_fields'] : [];
                foreach ($items as $index => $item) {
                    if (! is_array($item)) {
                        continue;
                    }

                    $references = array_merge(
                        $references,
                        $this->collectBlockReferences(
                            $item,
                            $itemFields,
                            $instanceId,
                            $languageCode,
                            $blockLabel,
                            $fieldPath . '[' . (int) $index . ']'
                        )
                    );
                }
                continue;
            }

            if (in_array($type, ['group', 'fieldset'], true)) {
                $nestedFields = is_array($fieldDef['fields'] ?? null) ? (array) $fieldDef['fields'] : [];
                $nestedData   = $blockData[$fieldKey] ?? null;
                if (is_array($nestedData) && $nestedFields !== []) {
                    $references = array_merge(
                        $references,
                        $this->collectBlockReferences(
                            $nestedData,
                            $nestedFields,
                            $instanceId,
                            $languageCode,
                            $blockLabel,
                            $fieldPath
                        )
                    );
                }
            }
        }

        return $references;
    }

    /**
     * @param list<array{hub_file_id:int, resource_type:string, resource_id:int, block_instance_id:int|null, role:string, label:?string, created_at:string}> $references
     */
    private function replaceReferences(string $resourceType, int $resourceId, array $references): void
    {
        if (! $this->db->tableExists('cms_file_references')) {
            throw new \RuntimeException(lang('Cms.file_references.missing_table'));
        }

        $this->db->transStart();

        $this->db->table('cms_file_references')
            ->where('resource_type', $resourceType)
            ->where('resource_id', $resourceId)
            ->delete();

        if ($references !== []) {
            $this->db->table('cms_file_references')->insertBatch($references);
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            throw new \RuntimeException(lang('Cms.file_references.sync_failed', [$resourceType, (string) $resourceId]));
        }
    }

    private function syncOwnedBlockInstances(string $ownerType, int $ownerId): void
    {
        $result = $this->db->table('cms_block_instances')
            ->select('id')
            ->where('owner_type', $ownerType)
            ->where('owner_id', $ownerId)
            ->get();
        $rows = $result ? $result->getResultArray() : [];

        foreach ($rows as $row) {
            $instanceId = (int) ($row['id'] ?? 0);
            if ($instanceId > 0) {
                $this->syncBlockInstance($instanceId);
            }
        }
    }

    /**
     * @param array<string, mixed> $translation
     */
    private function entryLabel(int $entryId, array $translation, string $languageCode): string
    {
        $title = trim((string) ($translation['title'] ?? ''));
        $slug  = trim((string) ($translation['slug'] ?? ''));
        $base  = $title !== '' ? $title : ($slug !== '' ? $slug : 'Entry #' . $entryId);

        return 'Entry: ' . $base . ' (' . $languageCode . ')';
    }

    /**
     * @param array<string, mixed> $translation
     */
    private function pageLabel(int $pageId, array $translation, string $languageCode): string
    {
        $title = trim((string) ($translation['title'] ?? ''));
        $slug  = trim((string) ($translation['slug'] ?? ''));
        $base  = $title !== '' ? $title : ($slug !== '' ? $slug : 'Page #' . $pageId);

        return 'Page: ' . $base . ' (' . $languageCode . ')';
    }

    /**
     * @param array<string, mixed> $instance
     */
    private function blockLabel(array $instance): string
    {
        $name = trim((string) ($instance['block_name'] ?? ''));
        $key  = trim((string) ($instance['block_key'] ?? ''));

        $base = $name !== '' ? $name : ($key !== '' ? $key : 'Block');

        return 'Block: ' . $base . ' #' . (int) ($instance['id'] ?? 0);
    }

    private function humanizeFieldLabel(string $label, string $path): string
    {
        $label = trim($label);
        if ($label !== '') {
            return $label;
        }

        return str_replace(['.', '[', ']'], [' > ', ' [', ''], $path);
    }

    private function buildRole(string $path, string $languageCode): string
    {
        $suffix = trim($languageCode) === '' ? '' : '.' . $languageCode;
        $role   = $path . $suffix;

        if (strlen($role) <= 50) {
            return $role;
        }

        // Roles participate in a unique key and are limited to 50 characters.
        // Keep a readable prefix and hash the complete path (including locale),
        // so long config paths and translated paths remain deterministic and unique.
        $hash = substr(sha1($role), 0, 10);
        $base = substr($role, 0, 39);

        return $base . '~' . $hash;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function schemaFields(string $schemaDefinition): array
    {
        if ($schemaDefinition === '') {
            return [];
        }

        $decoded = json_decode($schemaDefinition, true);
        if (! is_array($decoded)) {
            return [];
        }

        $fields = $decoded['fields'] ?? [];
        return is_array($fields) ? $fields : [];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function schemaConfigFields(string $schemaDefinition): array
    {
        if ($schemaDefinition === '') {
            return [];
        }

        $decoded = json_decode($schemaDefinition, true);
        if (! is_array($decoded)) {
            return [];
        }

        $fields = $decoded['config_fields'] ?? [];
        return is_array($fields) ? $fields : [];
    }

    /**
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
     * @return array{hub_file_id:int, resource_type:string, resource_id:int, block_instance_id:int|null, role:string, label:?string, created_at:string}
     */
    private function referenceRow(int $fileId, string $resourceType, int $resourceId, string $role, ?string $label): array
    {
        $label = $label === null ? null : mb_substr($label, 0, 255);

        return [
            'hub_file_id'       => $fileId,
            'resource_type'     => $resourceType,
            'resource_id'       => $resourceId,
            'block_instance_id' => $resourceType === 'block_instance' ? $resourceId : null,
            'role'              => $role,
            'label'             => $label,
            'created_at'        => date('Y-m-d H:i:s'),
        ];
    }

    private function languageCode(int $languageId): string
    {
        if ($this->languageCodeById === null) {
            $this->languageCodeById = [];
            $result = $this->db->table('cms_languages')->select('id, code')->get();
            foreach ($result ? $result->getResultArray() : [] as $row) {
                $id = (int) ($row['id'] ?? 0);
                $code = trim((string) ($row['code'] ?? ''));
                if ($id > 0 && $code !== '') {
                    $this->languageCodeById[$id] = $code;
                }
            }
        }

        return $this->languageCodeById[$languageId] ?? ('lang-' . $languageId);
    }
}
