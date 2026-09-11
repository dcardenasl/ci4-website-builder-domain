<?php

declare(strict_types=1);

namespace App\Libraries\Cms;

/** Raw persistent state, including hidden translations, used for optimistic concurrency. */
final readonly class EditorDocumentSnapshot
{
    /**
     * @param array<string, mixed> $owner
     * @param list<array<string, mixed>> $ownerTranslations
     * @param list<array<string, mixed>> $instances
     * @param list<array<string, mixed>> $translations
     * @param list<array<string, mixed>> $catalog
     * @param list<array<string, mixed>> $languages
     * @param array<string, mixed> $collection
     */
    public function __construct(
        public array $owner,
        public array $ownerTranslations,
        public array $instances,
        public array $translations,
        public array $catalog,
        public array $languages,
        public array $collection,
    ) {
    }

    public function version(): string
    {
        $state = get_object_vars($this);
        // Public traffic is not an editorial change. Neither timestamps nor
        // read counters can stand in for the content values being versioned.
        unset($state['owner']['view_count']);
        foreach ($state as $key => $rows) {
            $state[$key] = in_array($key, ['owner', 'collection'], true)
                ? self::persistentRow($rows)
                : array_map(self::persistentRow(...), $rows);
        }

        return hash('sha256', json_encode(self::canonical($state), JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION));
    }

    /**
     * Normalize only database columns, never identically named user content fields.
     *
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function persistentRow(array $row): array
    {
        unset($row['created_at'], $row['updated_at']);
        foreach (['block_config', 'block_data', 'schema_definition', 'block_template'] as $column) {
            if (is_string($row[$column] ?? null)) {
                $row[$column] = json_decode($row[$column], true, 512, JSON_THROW_ON_ERROR);
            }
        }

        return $row;
    }

    private static function canonical(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }
        if (! array_is_list($value)) {
            ksort($value);
        }
        foreach ($value as $key => $item) {
            $value[$key] = self::canonical($item);
        }

        return $value;
    }
}
