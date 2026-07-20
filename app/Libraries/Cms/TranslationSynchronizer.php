<?php

declare(strict_types=1);

namespace App\Libraries\Cms;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Model;

/**
 * Synchronizes a resource's translations in one database transaction using
 * only the required inserts, updates and deletes. Resource services provide
 * the table-specific row mapping.
 */
final class TranslationSynchronizer
{
    /**
     * @param BaseConnection<mixed, mixed> $database
     */
    public function __construct(private readonly BaseConnection $database)
    {
    }

    /**
     * Synchronize a resource's translation set atomically.
     *
     * This is intentionally model-agnostic: translation tables have different
     * field names, but they all share the same lifecycle semantics.
     *
     * @param Model $model Translation model configured with allowed fields.
     * @param array<int, array<string, mixed>> $translations
     * @param callable(array<string, mixed>): array<string, mixed> $mapRow
     */
    public function replace(
        Model $model,
        string $foreignKey,
        int $resourceId,
        array $translations,
        callable $mapRow,
    ): void {
        $managedLanguageIds = $this->activeLanguageIds();
        $incomingByLanguage = [];
        foreach ($translations as $translation) {
            if (! is_array($translation)) {
                continue;
            }

            $row = $mapRow($translation);
            $languageId = (int) ($row['language_id'] ?? 0);
            if ($languageId <= 0) {
                throw new \InvalidArgumentException('A translation must have a valid language_id.');
            }
            if (! isset($managedLanguageIds[$languageId])) {
                throw new \InvalidArgumentException('A translation must target an active language.');
            }
            if (isset($incomingByLanguage[$languageId])) {
                throw new \InvalidArgumentException('A resource cannot contain duplicate language translations.');
            }
            $incomingByLanguage[$languageId] = $row;
        }

        $existingRows = $model->where($foreignKey, $resourceId)->findAll();
        $existingByLanguage = [];
        foreach ($existingRows as $existing) {
            $existingArray = is_object($existing) && method_exists($existing, 'toArray')
                ? $existing->toArray()
                : (is_array($existing) ? $existing : []);
            $languageId = (int) ($existingArray['language_id'] ?? 0);
            $existingByLanguage[$languageId] = [
                'id'  => (int) ($existingArray['id'] ?? 0),
                'row' => $existingArray,
            ];
        }

        $deleteIds = [];
        foreach ($existingByLanguage as $languageId => $existing) {
            // Inactive language content is historical data and must not be
            // deleted merely because the editor only submits active languages.
            if (isset($managedLanguageIds[$languageId])
                && ! isset($incomingByLanguage[$languageId])
                && $existing['id'] > 0) {
                $deleteIds[] = $existing['id'];
            }
        }

        $updates = [];
        $inserts = [];
        foreach ($incomingByLanguage as $languageId => $row) {
            $existing = $existingByLanguage[$languageId] ?? null;
            if ($existing === null) {
                $row[$foreignKey] = $resourceId;
                $inserts[] = $row;
                continue;
            }

            if ($this->rowsDiffer($existing['row'], $row)) {
                $row['id'] = $existing['id'];
                $updates[] = $row;
            }
        }

        $this->database->transStart();
        if ($deleteIds !== []) {
            $model->whereIn('id', $deleteIds)->delete();
        }
        if ($updates !== []) {
            $model->updateBatch($updates, 'id');
        }
        if ($inserts !== []) {
            $model->insertBatch($inserts);
        }
        $this->database->transComplete();

        if ($this->database->transStatus() === false) {
            throw new \RuntimeException('Could not persist resource translations.');
        }
    }

    /** @return array<int, true> */
    private function activeLanguageIds(): array
    {
        $result = $this->database->table('cms_languages')
            ->select('id')
            ->where('is_active', 1)
            ->get()
        ;

        if ($result === false) {
            return [];
        }

        $rows = $result->getResultArray();

        $ids = [];
        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id > 0) {
                $ids[$id] = true;
            }
        }

        return $ids;
    }

    /**
     * @param array<string, mixed> $existing
     * @param array<string, mixed> $incoming
     */
    private function rowsDiffer(array $existing, array $incoming): bool
    {
        foreach ($incoming as $key => $value) {
            if ($key === 'id' || $key === 'language_id') {
                continue;
            }

            $existingValue = $existing[$key] ?? null;
            if ($this->normalizeValue($existingValue) !== $this->normalizeValue($value)) {
                return true;
            }
        }

        return false;
    }

    private function normalizeValue(mixed $value): mixed
    {
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        if (is_scalar($value) || $value === null) {
            return $value === null ? null : (string) $value;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
