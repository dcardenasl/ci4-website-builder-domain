<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\Libraries\Cms\TranslationAuditSupport;
use App\Libraries\Cms\TranslationResourceCatalog;

/**
 * Audits translation completeness for CMS block instances specifically —
 * schema-driven translatable fields nested inside `block_data` JSON, as
 * opposed to the flat-column translations the other resource types use.
 *
 * Extracted from TranslationAuditService, which composes this class.
 */
class BlockInstanceTranslationAuditor
{
    protected \App\Models\BlockInstanceTranslationModel $blockInstanceTranslationModel;

    public function __construct(private TranslationAuditSupport $support)
    {
        $this->blockInstanceTranslationModel = model(\App\Models\BlockInstanceTranslationModel::class);
    }

    /**
     * @param list<object> $activeLanguages
     * @param array<string, mixed> $filters
     * @return list<array<string, mixed>>
     */
    public function audit(array $activeLanguages, array $filters): array
    {
        $instances = $this->getBlockInstancesWithTypes();
        $translationsByInstance = $this->support->groupTranslationsByResource(
            $this->blockInstanceTranslationModel->findAll(),
            'instance_id'
        );

        $issues = [];
        foreach ($instances as $instance) {
            $instanceId = (int) ($instance['id'] ?? 0);
            if ($instanceId <= 0) {
                continue;
            }

            $translatableFields = $this->getTranslatableBlockFieldDefinitions($instance['schema_definition'] ?? null);
            if ($translatableFields === []) {
                continue;
            }

            $translations = $translationsByInstance[$instanceId] ?? [];
            foreach ($activeLanguages as $lang) {
                $langId = (int) $lang->id;
                if (! $this->support->languageFilterAllows($filters, $langId)) {
                    continue;
                }

                $translation = $translations[$langId] ?? null;
                [$status, $detail] = $this->support->evaluateTranslationState(
                    $translation,
                    $translations,
                    $translatableFields,
                    $langId,
                    function (array $row, string $fieldKey, array $fieldDefinition): mixed {
                        return $this->extractBlockFieldValue($row, $fieldKey, $fieldDefinition);
                    }
                );
                if ($status === 'complete') {
                    continue;
                }

                $issues[] = $this->support->buildIssue(
                    'block_instance',
                    $instanceId,
                    'Block Instance #' . $instanceId . ' (' . (string) ($instance['block_key'] ?? '') . ')',
                    $langId,
                    (string) ($lang->code ?? ''),
                    $status,
                    $detail,
                    [
                        'owner_type' => (string) ($instance['owner_type'] ?? ''),
                        'owner_id' => (int) ($instance['owner_id'] ?? 0),
                        'block_key' => (string) ($instance['block_key'] ?? ''),
                    ]
                );
            }
        }

        return $issues;
    }

    /**
     * Per-instance audit used by TranslationAuditService::auditResource() for the
     * 'block_instance' resource type: resolves the instance + its schema + its
     * translations, ready for the caller to run through evaluateTranslationState().
     *
     * @return array{0: array<string, mixed>, 1: array<string, array{required: bool, type: string, data_key: string}>, 2: array<int, array<string, mixed>>, 3: callable(array<string, mixed>, string, array<string, mixed>): mixed}|null
     */
    public function resolveForResource(int $resourceId): ?array
    {
        $instance = $this->getBlockInstanceWithType($resourceId);
        if ($instance === null) {
            return null;
        }

        $translations = $this->support->groupTranslationsByResource(
            $this->blockInstanceTranslationModel->where('instance_id', $resourceId)->findAll(),
            'instance_id'
        )[$resourceId] ?? [];

        $fieldDefinitions = $this->getTranslatableBlockFieldDefinitions($instance['schema_definition'] ?? null);
        $valueResolver = function (array $row, string $fieldKey, array $fieldDefinition): mixed {
            return $this->extractBlockFieldValue($row, $fieldKey, $fieldDefinition);
        };

        return [$instance, $fieldDefinitions, $translations, $valueResolver];
    }

    public function countAuditable(): int
    {
        $instances = $this->getBlockInstancesWithTypes();
        $count = 0;

        foreach ($instances as $instance) {
            if ($this->getTranslatableBlockFieldDefinitions($instance['schema_definition'] ?? null) !== []) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getBlockInstancesWithTypes(): array
    {
        $db = \Config\Database::connect();
        $query = $db->table('cms_block_instances i')
            ->select('i.*, b.block_key, b.schema_definition')
            ->join('cms_content_blocks b', 'b.id = i.block_id')
            ->where('i.is_active', 1)
            ->orderBy('i.sort_order', 'ASC')
            ->get();

        /** @var list<array<string, mixed>> $rows */
        $rows = $query ? $query->getResultArray() : [];

        return $rows;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getBlockInstanceWithType(int $resourceId): ?array
    {
        $db = \Config\Database::connect();
        $query = $db->table('cms_block_instances i')
            ->select('i.*, b.block_key, b.schema_definition')
            ->join('cms_content_blocks b', 'b.id = i.block_id')
            ->where('i.id', $resourceId)
            ->limit(1)
            ->get();

        $instance = $query ? $query->getRowArray() : null;

        return is_array($instance) ? $instance : null;
    }

    /**
     * @param array<string, mixed>|array<int, mixed>|string|null $schemaDefinition
     * @return array<string, array{required: bool, type: string, data_key: string}>
     */
    private function getTranslatableBlockFieldDefinitions(mixed $schemaDefinition): array
    {
        $schema = is_string($schemaDefinition)
            ? json_decode($schemaDefinition, true)
            : (is_array($schemaDefinition) ? $schemaDefinition : []);

        if (!is_array($schema)) {
            return [];
        }

        $fields = $schema['fields'] ?? [];
        if (!is_array($fields) || $fields === []) {
            return [];
        }

        $translatable = [];
        foreach ($fields as $fieldKey => $fieldDef) {
            if (!is_array($fieldDef)) {
                continue;
            }

            if (!TranslationResourceCatalog::isAuditableBlockField($fieldDef)) {
                continue;
            }

            $fieldKey = (string) $fieldKey;
            $translatable[$fieldKey] = [
                'required' => (bool) ($fieldDef['required'] ?? false),
                'type' => strtolower((string) ($fieldDef['type'] ?? 'string')),
                'data_key' => $fieldKey,
            ];
        }

        return $translatable;
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, mixed> $fieldDefinition
     */
    private function extractBlockFieldValue(array $row, string $fieldKey, array $fieldDefinition): mixed
    {
        $blockData = $row['block_data'] ?? null;
        if (is_string($blockData)) {
            $decoded = json_decode($blockData, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $blockData = $decoded;
            }
        }

        if (is_object($blockData)) {
            $blockData = (array) $blockData;
        }

        if (!is_array($blockData)) {
            $blockData = [];
        }

        $dataKey = (string) ($fieldDefinition['data_key'] ?? $fieldKey);

        return $blockData[$dataKey] ?? null;
    }
}
