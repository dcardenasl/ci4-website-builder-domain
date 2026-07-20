<?php

declare(strict_types=1);

namespace App\Libraries\Cms;

/**
 * Stateless helpers shared by TranslationAuditService and
 * BlockInstanceTranslationAuditor: normalizing rows, grouping translations by
 * resource id, and deciding whether a translation is complete/incomplete/mismatched.
 *
 * Extracted from TranslationAuditService (was 1234 lines) to keep the
 * orchestrator focused on dispatch and each collaborator independently testable.
 */
class TranslationAuditSupport
{
    /**
     * @param array<int|string, mixed>|object $resource
     * @return array<string, mixed>
     */
    public function toArray(array|object $resource): array
    {
        if (is_array($resource)) {
            /** @var array<string, mixed> $resource */
            return $resource;
        }

        if (method_exists($resource, 'toArray')) {
            /** @var array<string, mixed> $data */
            $data = $resource->toArray();

            return $data;
        }

        return (array) $resource;
    }

    /**
     * @param list<array<int|string, mixed>|object> $rows
     * @return array<int, array<int, array<string, mixed>>>
     */
    public function groupTranslationsByResource(array $rows, string $foreignKey): array
    {
        $indexed = [];
        foreach ($rows as $row) {
            $data = $this->toArray($row);
            $resourceId = (int) ($data[$foreignKey] ?? 0);
            $langId = (int) ($data['language_id'] ?? 0);
            if ($resourceId <= 0 || $langId <= 0) {
                continue;
            }

            $indexed[$resourceId][$langId] = $data;
        }

        return $indexed;
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function languageFilterAllows(array $filters, int $languageId): bool
    {
        if (!isset($filters['language_id'])) {
            return true;
        }

        return (int) $filters['language_id'] === $languageId;
    }

    public function isBlank(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        if (is_array($value)) {
            return $value === [];
        }

        return false;
    }

    /**
     * @param array<string, mixed> $extraData
     * @return array<string, mixed>
     */
    public function buildIssue(
        string $resourceType,
        int $resourceId,
        string $referenceName,
        int $languageId,
        string $languageCode,
        string $status,
        string $detail,
        array $extraData = []
    ): array {
        $issue = [
            'resource' => $resourceType,
            'resource_id' => $resourceId,
            'reference_name' => $referenceName,
            'language_id' => $languageId,
            'language_code' => $languageCode,
            'status' => $status,
            'detail' => $detail,
        ];

        if ($extraData !== []) {
            $issue['extra_data'] = $extraData;
        }

        return $issue;
    }

    /**
     * @param array<string, mixed>|object|null $translation
     * @param array<int, array<string, mixed>|object|null> $translationsByLanguage
     * @param array<string, mixed> $fieldDefinitions
     * @return array{0: string, 1: string}
     */
    public function evaluateTranslationState(
        array|object|null $translation,
        array $translationsByLanguage,
        array $fieldDefinitions,
        int $languageId,
        callable $valueResolver,
        ?string $resourceUpdatedAt = null
    ): array {
        if ($translation === null) {
            return ['missing', 'Translation is missing completely'];
        }

        $row = $this->toArray($translation);
        $missingRequired = [];
        $mismatchedOptional = [];

        foreach ($fieldDefinitions as $fieldKey => $fieldDefinition) {
            $fieldKey = (string) $fieldKey;
            $fieldDefinition = is_array($fieldDefinition) ? $fieldDefinition : [];
            $currentValue = $valueResolver($row, $fieldKey, $fieldDefinition);

            if ($this->isBlank($currentValue)) {
                if ((bool) ($fieldDefinition['required'] ?? false)) {
                    $missingRequired[] = $fieldKey;
                    continue;
                }

                foreach ($translationsByLanguage as $otherLanguageId => $otherTranslation) {
                    if ((int) $otherLanguageId === $languageId || $otherTranslation === null) {
                        continue;
                    }

                    $otherRow = $this->toArray($otherTranslation);
                    $otherValue = $valueResolver($otherRow, $fieldKey, $fieldDefinition);
                    if (! $this->isBlank($otherValue)) {
                        $mismatchedOptional[] = $fieldKey;
                        break;
                    }
                }
            }
        }

        $missingRequired = array_values(array_unique($missingRequired));
        if ($missingRequired !== []) {
            return ['incomplete', 'Missing required fields: ' . implode(', ', $missingRequired)];
        }

        $mismatchedOptional = array_values(array_unique($mismatchedOptional));
        if ($mismatchedOptional !== []) {
            return ['mismatch', 'Inconsistent fields: ' . implode(', ', $mismatchedOptional)];
        }

        if ($resourceUpdatedAt !== null) {
            $sourceTimestamp = strtotime($resourceUpdatedAt);
            $translationTimestamp = strtotime((string) ($row['updated_at'] ?? ''));
            if ($sourceTimestamp !== false && $translationTimestamp !== false && $translationTimestamp < $sourceTimestamp) {
                return ['outdated', 'Translation predates the latest source update'];
            }
        }

        return ['complete', ''];
    }
}
