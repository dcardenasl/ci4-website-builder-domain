<?php

declare(strict_types=1);

namespace App\Libraries\Cms;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Model;

/**
 * Persists a resource's translations in one database transaction and one
 * batch insert. Resource services provide the table-specific row mapping.
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
     * Replace a resource's translation set atomically.
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
        $rows = [];
        foreach ($translations as $translation) {
            if (! is_array($translation)) {
                continue;
            }

            $row = $mapRow($translation);
            $row[$foreignKey] = $resourceId;
            $rows[] = $row;
        }

        $this->database->transStart();
        $model->where($foreignKey, $resourceId)->delete();
        if ($rows !== []) {
            $model->insertBatch($rows);
        }
        $this->database->transComplete();

        if ($this->database->transStatus() === false) {
            throw new \RuntimeException('Could not persist resource translations.');
        }
    }
}
