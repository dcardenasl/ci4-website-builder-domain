<?php

declare(strict_types=1);

if (! function_exists('collection_resolve_text')) {
    /**
     * Resolve a collection display string from a prioritized list of fields.
     *
     * @param array<string, mixed> $collection
     * @param list<string> $fields
     */
    function collection_resolve_text(array $collection, array $fields, bool $humanizeFallback = false): string
    {
        foreach ($fields as $field) {
            $value = trim((string) ($collection[$field] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        if (! $humanizeFallback) {
            return '';
        }

        foreach (['slug', 'collection_key'] as $field) {
            $value = trim((string) ($collection[$field] ?? ''));
            if ($value === '') {
                continue;
            }

            $value = preg_replace('/[-_]+/', ' ', $value) ?? $value;

            if (function_exists('mb_convert_case')) {
                return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
            }

            return ucwords($value);
        }

        return '';
    }
}

if (! function_exists('collection_display_title')) {
    /**
     * Resolve the public title for a collection without hardcoded section names.
     *
     * @param array<string, mixed> $collection
     */
    function collection_display_title(array $collection): string
    {
        return collection_resolve_text($collection, ['listing_title', 'name'], true);
    }
}

if (! function_exists('collection_display_intro')) {
    /**
     * Resolve the public intro for a collection.
     *
     * @param array<string, mixed> $collection
     */
    function collection_display_intro(array $collection): string
    {
        return collection_resolve_text($collection, ['listing_intro', 'description'], false);
    }
}
