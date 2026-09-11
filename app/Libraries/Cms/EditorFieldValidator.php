<?php

declare(strict_types=1);

namespace App\Libraries\Cms;

use dcardenasl\Ci4ApiCore\Exceptions\ValidationException;

/** Validates edited fields only; unknown persisted fields survive an unrelated patch. */
final class EditorFieldValidator
{
    /**
     * @param array<string, mixed> $changes
     * @param array<string, mixed> $schema
     */
    public function validate(array $changes, array $schema): void
    {
        foreach ($changes as $key => $value) {
            $definition = $schema[$key] ?? null;
            if (! is_array($definition)) {
                throw new ValidationException(lang('Editor.invalidField'), [$key => lang('Editor.invalidField')]);
            }
            $type = strtolower((string) ($definition['type'] ?? 'text'));
            if ($value === null || $value === '') {
                continue; // Empty draft fields are valid, even when required at publication.
            }
            $valid = match ($type) {
                'text', 'string', 'textarea', 'richtext', 'rich_text', 'rich-text', 'html', 'url', 'date', 'datetime', 'date_time' => is_string($value),
                'number', 'float', 'decimal' => is_int($value) || (is_float($value) && is_finite($value)),
                'int', 'integer' => is_int($value),
                'bool', 'boolean' => is_bool($value),
                'select' => $this->validOption($value, $definition['options'] ?? []),
                'color' => $this->validColor($value),
                'media_reference' => $this->validMedia($value),
                default => false, // Repeater/collection and custom fields use the classic editor.
            };
            if (! $valid) {
                throw new ValidationException(lang('Editor.invalidField'), [$key => lang('Editor.invalidField')]);
            }
        }
    }

    /**
     * Hex and rgb()/rgba() only.
     *
     * The catalog itself ships `rgba(15, 23, 42, 0.4)` as the default of a
     * `color` field, so hex alone would reject the platform's own content. This
     * stays an allowlist: anything else — `var()`, `url()`, a bare keyword — is
     * still refused rather than passed into a style attribute.
     */
    private function validColor(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        if (preg_match('/^#(?:[a-fA-F0-9]{3}|[a-fA-F0-9]{6}|[a-fA-F0-9]{8})$/D', $value) === 1) {
            return true;
        }

        return preg_match('/^rgba?\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*(?:,\s*(?:0|1|0?\.\d+)\s*)?\)$/D', $value) === 1;
    }

    private function validOption(mixed $value, mixed $options): bool
    {
        if (! is_array($options) || ! is_scalar($value)) {
            return false;
        }
        foreach ($options as $key => $option) {
            $candidate = is_array($option) ? ($option['value'] ?? null) : (array_is_list($options) ? $option : $key);
            if ($value === $candidate) {
                return true;
            }
        }

        return false;
    }

    private function validMedia(mixed $value): bool
    {
        if (! is_array($value) || ! is_string($value['source_kind'] ?? null)) {
            return false;
        }
        if (in_array($value['source_kind'], ['media_file', 'hub_file'], true)) {
            return is_int($value['file_id'] ?? null) && $value['file_id'] > 0;
        }
        if ($value['source_kind'] !== 'external_url' || ! is_string($value['url'] ?? null)) {
            return false;
        }
        $url = $value['url'];

        return filter_var($url, FILTER_VALIDATE_URL) !== false && in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true);
    }
}
