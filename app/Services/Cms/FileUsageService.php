<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\Models\EntryTranslationModel;
use App\Models\PageTranslationModel;
use App\Models\SettingModel;
use CodeIgniter\Database\BaseConnection;
use Config\Database;

/**
 * Reports all places within the Domain CMS where a given Hub file ID is referenced.
 * Each result follows the shared usages contract: source, resource, resource_id, role, label.
 *
 * Sources scanned:
 *   - cms_entry_translations  (featured_file_id, og_image_file_id)
 *   - cms_page_translations   (og_image_file_id)
 *   - cms_settings            (setting_type = 'file_id', setting_value = id)
 *   - cms_block_instance_translations (block_data JSON, all {field}_file_id keys per schema)
 *
 * @phpstan-type UsageItem array{source: string, resource: string, resource_id: int, role: string, label: string|null}
 */
class FileUsageService
{
    /** @var BaseConnection<mixed, mixed> */
    private BaseConnection $db;

    /**
     * @param BaseConnection<mixed, mixed>|null $db
     */
    public function __construct(
        protected EntryTranslationModel $entryTranslationModel,
        protected PageTranslationModel $pageTranslationModel,
        protected SettingModel $settingModel,
        ?BaseConnection $db = null,
    ) {
        $this->db = $db ?? Database::connect();
    }

    /**
     * @return list<UsageItem>
     */
    public function getUsagesByHubFileId(int $hubFileId): array
    {
        return array_values(array_merge(
            $this->entryFeaturedImageUsages($hubFileId),
            $this->entryOgImageUsages($hubFileId),
            $this->pageOgImageUsages($hubFileId),
            $this->settingFileUsages($hubFileId),
            $this->blockReferenceUsages($hubFileId),
        ));
    }

    /** @return list<UsageItem> */
    private function entryFeaturedImageUsages(int $hubFileId): array
    {
        /** @var list<array{entry_id: int|string, title: string|null}> $rows */
        $rows = $this->entryTranslationModel
            ->select('entry_id, title')
            ->where('featured_file_id', $hubFileId)
            ->asArray()
            ->findAll();

        return array_map(fn (array $row) => [
            'source'      => 'domain',
            'resource'    => 'entries',
            'resource_id' => (int) $row['entry_id'],
            'role'        => 'featured_image',
            'label'       => isset($row['title']) ? (string) $row['title'] : null,
        ], $rows);
    }

    /** @return list<UsageItem> */
    private function entryOgImageUsages(int $hubFileId): array
    {
        /** @var list<array{entry_id: int|string, title: string|null}> $rows */
        $rows = $this->entryTranslationModel
            ->select('entry_id, title')
            ->where('og_image_file_id', $hubFileId)
            ->asArray()
            ->findAll();

        return array_map(fn (array $row) => [
            'source'      => 'domain',
            'resource'    => 'entries',
            'resource_id' => (int) $row['entry_id'],
            'role'        => 'og_image',
            'label'       => isset($row['title']) ? (string) $row['title'] : null,
        ], $rows);
    }

    /** @return list<UsageItem> */
    private function pageOgImageUsages(int $hubFileId): array
    {
        /** @var list<array{page_id: int|string, title: string|null}> $rows */
        $rows = $this->pageTranslationModel
            ->select('page_id, title')
            ->where('og_image_file_id', $hubFileId)
            ->asArray()
            ->findAll();

        return array_map(fn (array $row) => [
            'source'      => 'domain',
            'resource'    => 'pages',
            'resource_id' => (int) $row['page_id'],
            'role'        => 'og_image',
            'label'       => isset($row['title']) ? (string) $row['title'] : null,
        ], $rows);
    }

    /** @return list<UsageItem> */
    private function settingFileUsages(int $hubFileId): array
    {
        /** @var list<array{id: int|string, setting_key: string|null}> $rows */
        $rows = $this->settingModel
            ->select('id, setting_key')
            ->where('setting_type', 'file_id')
            ->where('setting_value', (string) $hubFileId)
            ->asArray()
            ->findAll();

        return array_map(fn (array $row) => [
            'source'      => 'domain',
            'resource'    => 'settings',
            'resource_id' => (int) $row['id'],
            'role'        => 'setting_value',
            'label'       => isset($row['setting_key']) ? (string) $row['setting_key'] : null,
        ], $rows);
    }

    /**
     * Reads block usages from the canonical file_references table when it is
     * available, and falls back to scanning block JSON only if the reference
     * table is absent (for bootstrap or partial-schema environments).
     *
     * @return list<UsageItem>
     */
    private function blockReferenceUsages(int $hubFileId): array
    {
        if ($this->db->tableExists('file_references')) {
            $result = $this->db->table('file_references fr')
                ->select('fr.resource_id, fr.role, fr.label, bi.owner_type, bi.owner_id, bt.block_key, bt.name as block_name')
                ->join('cms_block_instances bi', 'bi.id = fr.resource_id', 'left')
                ->join('cms_content_blocks bt', 'bt.id = bi.block_id', 'left')
                ->where('fr.file_id', $hubFileId)
                ->where('fr.resource_type', 'block_instance')
                ->get();
            $rows = $result ? $result->getResultArray() : [];

            return array_values(array_map(static function (array $row) use ($hubFileId): array {
                return [
                    'source'      => 'domain',
                    'resource'    => 'block_instances',
                    'resource_id' => (int) ($row['resource_id'] ?? 0),
                    'role'        => (string) ($row['role'] ?? 'block_instance'),
                    'label'       => isset($row['label']) && trim((string) $row['label']) !== ''
                        ? (string) $row['label']
                        : null,
                    'context'     => [
                        'owner_type' => (string) ($row['owner_type'] ?? ''),
                        'owner_id'   => (int) ($row['owner_id'] ?? 0),
                        'file_id'    => $hubFileId,
                        'block_key'  => (string) ($row['block_key'] ?? ''),
                        'block_name' => (string) ($row['block_name'] ?? ''),
                    ],
                ];
            }, $rows));
        }

        return array_values(array_merge(
            $this->legacyBlockDataFileUsages($hubFileId),
            $this->legacyBlockConfigFileUsages($hubFileId),
        ));
    }

    /**
     * Legacy fallback for environments that do not have the canonical
     * file_references table yet.
     *
     * @return list<UsageItem>
     */
    private function legacyBlockDataFileUsages(int $hubFileId): array
    {
        $blockTypeResult = $this->db->table('cms_content_blocks')
            ->select('id, block_key, name, schema_definition')
            ->where('is_active', 1)
            ->get();
        $blockTypeRows   = $blockTypeResult ? $blockTypeResult->getResultArray() : [];

        if (empty($blockTypeRows)) {
            return [];
        }

        /** @var array<int, list<string>> $fileFieldsByType */
        $fileFieldsByType = [];
        foreach ($blockTypeRows as $bt) {
            $keys = $this->extractFileFieldKeys((string) ($bt['schema_definition'] ?? ''));
            if (! empty($keys)) {
                $fileFieldsByType[(int) $bt['id']] = $keys;
            }
        }

        if (empty($fileFieldsByType)) {
            return [];
        }

        $usages = [];
        $seen   = [];

        foreach ($fileFieldsByType as $blockTypeId => $fieldKeys) {
            foreach ($fieldKeys as $fieldKey) {
                $jsonPath = '$.' . $fieldKey . '_file_id';

                $blockResult = $this->db->table('cms_block_instance_translations bit')
                    ->select('bi.id as instance_id, bi.owner_type, bi.owner_id, bt.block_key, bt.name as block_name, bit.block_data')
                    ->join('cms_block_instances bi', 'bi.id = bit.instance_id')
                    ->join('cms_content_blocks bt', 'bt.id = bi.block_id')
                    ->where('bi.block_id', $blockTypeId)
                    ->where("JSON_EXTRACT(bit.block_data, '{$jsonPath}') = {$hubFileId}")
                    ->get();
                $rows = $blockResult ? $blockResult->getResultArray() : [];

                foreach ($rows as $row) {
                    $key = $row['instance_id'] . '|' . $fieldKey;
                    if (isset($seen[$key])) {
                        continue;
                    }
                    $seen[$key] = true;

                    $label = $this->buildBlockLabel(
                        (string) $row['block_name'],
                        (string) $row['owner_type'],
                        (int) $row['owner_id']
                    );

                    $usages[] = [
                        'source'      => 'domain',
                        'resource'    => 'block_instances',
                        'resource_id' => (int) $row['instance_id'],
                        'role'        => $fieldKey,
                        'label'       => $label,
                        'context'     => [
                            'owner_type' => $row['owner_type'],
                            'owner_id'   => (int) $row['owner_id'],
                        ],
                    ];
                }
            }
        }

        return $usages;
    }

    /**
     * Legacy fallback for media reference config fields when the canonical
     * `file_references` table is unavailable.
     *
     * @return list<UsageItem>
     */
    private function legacyBlockConfigFileUsages(int $hubFileId): array
    {
        $blockTypeResult = $this->db->table('cms_content_blocks')
            ->select('id, block_key, name, schema_definition')
            ->where('is_active', 1)
            ->get();
        $blockTypeRows = $blockTypeResult ? $blockTypeResult->getResultArray() : [];

        if (empty($blockTypeRows) || ! $this->db->tableExists('cms_block_instances')) {
            return [];
        }

        /** @var array<int, list<array{path: string, type: string}>> $pathsByType */
        $pathsByType = [];
        foreach ($blockTypeRows as $bt) {
            $paths = $this->extractConfigReferencePaths((string) ($bt['schema_definition'] ?? ''));
            if ($paths !== []) {
                $pathsByType[(int) $bt['id']] = $paths;
            }
        }

        if ($pathsByType === []) {
            return [];
        }

        $usages = [];
        $seen = [];

        foreach ($pathsByType as $blockTypeId => $paths) {
            foreach ($paths as $pathInfo) {
                $path = (string) ($pathInfo['path'] ?? '');
                $type = (string) ($pathInfo['type'] ?? '');
                if ($path === '' || ! in_array($type, ['file', 'media_reference'], true)) {
                    continue;
                }

                $jsonPath = $type === 'file'
                    ? '$.' . $path . '_file_id'
                    : '$.' . $path . '.file_id';

                $blockResult = $this->db->table('cms_block_instances bi')
                    ->select('bi.id as instance_id, bi.owner_type, bi.owner_id, bt.block_key, bt.name as block_name')
                    ->join('cms_content_blocks bt', 'bt.id = bi.block_id')
                    ->where('bi.block_id', $blockTypeId)
                    ->where("JSON_EXTRACT(bi.block_config, '{$jsonPath}') = {$hubFileId}")
                    ->get();
                $rows = $blockResult ? $blockResult->getResultArray() : [];

                foreach ($rows as $row) {
                    $key = $row['instance_id'] . '|' . $path;
                    if (isset($seen[$key])) {
                        continue;
                    }
                    $seen[$key] = true;

                    $label = $this->buildBlockLabel(
                        (string) $row['block_name'],
                        (string) $row['owner_type'],
                        (int) $row['owner_id']
                    );

                    $usages[] = [
                        'source'      => 'domain',
                        'resource'    => 'block_instances',
                        'resource_id' => (int) $row['instance_id'],
                        'role'        => 'config.' . $path,
                        'label'       => $label,
                        'context'     => [
                            'owner_type' => $row['owner_type'],
                            'owner_id'   => (int) $row['owner_id'],
                        ],
                    ];
                }
            }
        }

        return $usages;
    }

    /**
     * Recursively extract all field keys of type 'file' from a schema_definition JSON.
     * Handles top-level fields and items within repeaters.
     *
     * @return list<string>
     */
    private function extractFileFieldKeys(string $schemaDef, string $prefix = ''): array
    {
        if ($schemaDef === '') {
            return [];
        }

        $schema = json_decode($schemaDef, true);
        if (! is_array($schema)) {
            return [];
        }

        $fields = $schema['fields'] ?? [];
        if (! is_array($fields)) {
            return [];
        }

        return $this->collectFileKeysFromFields($fields, $prefix);
    }

    /**
     * @return list<array{path: string, type: string}>
     */
    private function extractConfigReferencePaths(string $schemaDef, string $prefix = ''): array
    {
        if ($schemaDef === '') {
            return [];
        }

        $schema = json_decode($schemaDef, true);
        if (! is_array($schema)) {
            return [];
        }

        $fields = $schema['config_fields'] ?? [];
        if (! is_array($fields)) {
            return [];
        }

        return $this->collectConfigReferencePaths($fields, $prefix);
    }

    /**
     * @param  array<string, array<string, mixed>> $fields
     * @return list<string>
     */
    private function collectFileKeysFromFields(array $fields, string $prefix = ''): array
    {
        $keys = [];

        foreach ($fields as $fieldKey => $fieldDef) {
            $type       = strtolower((string) ($fieldDef['type'] ?? 'string'));
            $qualifiedKey = $prefix !== '' ? $prefix . '.' . $fieldKey : $fieldKey;

            if ($type === 'file') {
                $keys[] = $qualifiedKey;
            } elseif ($type === 'repeater') {
                $itemFields = $fieldDef['item_fields'] ?? [];
                if (is_array($itemFields) && ! empty($itemFields)) {
                    // Repeater items in JSON are arrays; JSON_EXTRACT with [*] handles them
                    $keys = array_merge(
                        $keys,
                        $this->collectFileKeysFromFields($itemFields, $qualifiedKey . '[*]')
                    );
                }
            } elseif (in_array($type, ['group', 'fieldset'], true)) {
                $nestedFields = $fieldDef['fields'] ?? [];
                if (is_array($nestedFields)) {
                    $keys = array_merge(
                        $keys,
                        $this->collectFileKeysFromFields($nestedFields, $qualifiedKey)
                    );
                }
            }
        }

        return $keys;
    }

    /**
     * @param array<string, array<string, mixed>> $fields
     * @return list<array{path: string, type: string}>
     */
    private function collectConfigReferencePaths(array $fields, string $prefix = ''): array
    {
        $paths = [];

        foreach ($fields as $fieldKey => $fieldDef) {
            $type = strtolower((string) ($fieldDef['type'] ?? 'string'));
            $qualifiedKey = $prefix !== '' ? $prefix . '.' . $fieldKey : (string) $fieldKey;

            if (in_array($type, ['file', 'media_reference'], true)) {
                $paths[] = [
                    'path' => $qualifiedKey,
                    'type' => $type,
                ];
                continue;
            }

            if ($type === 'repeater') {
                $itemFields = $fieldDef['item_fields'] ?? [];
                if (is_array($itemFields) && ! empty($itemFields)) {
                    $paths = array_merge(
                        $paths,
                        $this->collectConfigReferencePaths($itemFields, $qualifiedKey . '[*]')
                    );
                }
                continue;
            }

            if (in_array($type, ['group', 'fieldset'], true)) {
                $nestedFields = $fieldDef['fields'] ?? [];
                if (is_array($nestedFields)) {
                    $paths = array_merge(
                        $paths,
                        $this->collectConfigReferencePaths($nestedFields, $qualifiedKey)
                    );
                }
            }
        }

        return $paths;
    }

    private function buildBlockLabel(string $blockName, string $ownerType, int $ownerId): string
    {
        $ownerLabel = match ($ownerType) {
            'page'  => $this->pageTitle($ownerId),
            'entry' => $this->entryTitle($ownerId),
            default => ucfirst($ownerType) . ' #' . $ownerId,
        };

        return $blockName . ' — ' . $ownerLabel;
    }

    private function pageTitle(int $pageId): string
    {
        $pageResult = $this->db->table('cms_page_translations')
            ->select('title')
            ->where('page_id', $pageId)
            ->limit(1)
            ->get();
        $row = $pageResult ? $pageResult->getRowArray() : null;

        return is_array($row) && isset($row['title']) ? (string) $row['title'] : 'Página #' . $pageId;
    }

    private function entryTitle(int $entryId): string
    {
        $entryResult = $this->db->table('cms_entry_translations')
            ->select('title')
            ->where('entry_id', $entryId)
            ->limit(1)
            ->get();
        $row = $entryResult ? $entryResult->getRowArray() : null;

        return is_array($row) && isset($row['title']) ? (string) $row['title'] : 'Entrada #' . $entryId;
    }
}
