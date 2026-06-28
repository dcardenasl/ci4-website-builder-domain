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
            $this->blockDataFileUsages($hubFileId),
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
     * Scan cms_block_instance_translations.block_data for any {field}_file_id
     * matching the given Hub file ID, using each block type's schema_definition
     * to know which fields are of type 'file'.
     *
     * Strategy: join block types to get schema, extract all 'file' field keys,
     * then use MySQL JSON_EXTRACT per field key to find matches. One query per
     * unique (field_key) across all active block types.
     *
     * @return list<UsageItem>
     */
    private function blockDataFileUsages(int $hubFileId): array
    {
        // Step 1: load all block types with their schema to know which JSON keys to probe
        $blockTypeResult = $this->db->table('cms_content_blocks')
            ->select('id, block_key, name, schema_definition')
            ->where('is_active', 1)
            ->get();
        $blockTypeRows   = $blockTypeResult ? $blockTypeResult->getResultArray() : [];

        if (empty($blockTypeRows)) {
            return [];
        }

        // Build map: block_type_id → list of file field keys (including nested in repeaters)
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

        // Step 2: for each (block_type_id, field_key) pair, query for matching block instances
        $usages = [];
        $seen   = [];  // deduplicate by instance_id + field_key

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
