<?php

declare(strict_types=1);

namespace App\Libraries\Cms;

/** Resolves display copy only. Callers must retain raw translations for editing. */
final class TranslationFallbackResolver
{
    /**
     * Structured fields remain atomic; their item indexes are not translation identities.
     *
     * @param array<string, mixed> $active
     * @param array<string, mixed> $default
     * @param array<string, mixed> $fields
     * @return array{block_data: array<string, mixed>, is_fallback: bool, fallback_fields: list<string>}
     */
    public function resolve(array $active, array $default, array $fields, bool $isDefault = false): array
    {
        $resolved = $active;
        $fallbackFields = [];

        if (! $isDefault) {
            foreach ($fields as $key => $definition) {
                if (! is_array($definition)) {
                    continue;
                }
                $type = is_string($definition['type'] ?? null) ? $definition['type'] : '';
                if ($this->isMissing($active[$key] ?? null, $type)
                    && ! $this->isMissing($default[$key] ?? null, $type)) {
                    $resolved[$key] = $default[$key];
                    $fallbackFields[] = $key;
                }
            }
        }

        return ['block_data' => $resolved, 'is_fallback' => $fallbackFields !== [], 'fallback_fields' => $fallbackFields];
    }

    private function isMissing(mixed $value, string $type): bool
    {
        if ($value === null) {
            return true;
        }
        if (! is_string($value)) {
            return false;
        }
        if (! in_array(strtolower($type), ['richtext', 'rich_text', 'rich-text', 'html'], true)) {
            return trim($value) === '';
        }
        if (preg_match('/<(?:img|video|audio|iframe|svg|hr)\b/i', $value) === 1) {
            return false;
        }

        $text = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return preg_match('/^[\s\x{00a0}\x{200b}]*$/u', $text) === 1;
    }
}
