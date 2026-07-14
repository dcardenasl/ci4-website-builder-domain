<?php

declare(strict_types=1);

namespace App\Libraries\Cms;

use App\Exceptions\BlockTemplateValidationException;

final class BlockTemplateNormalizer
{
    /**
     * Normalizes a raw block template payload into the canonical v1 shape.
     *
     * @param mixed $template
     * @return array<string, mixed>|null
     */
    public static function normalize(mixed $template): ?array
    {
        if ($template === null || $template === '') {
            return null;
        }

        if (is_object($template)) {
            $json = json_encode($template);
            if ($json === false) {
                throw new BlockTemplateValidationException('Failed to encode template as JSON');
            }
            $template = json_decode($json, true);
        }

        if (is_string($template)) {
            $decoded = json_decode($template, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new BlockTemplateValidationException('block_template must be valid JSON: ' . json_last_error_msg());
            }

            $template = $decoded;
        }

        if (! is_array($template)) {
            throw new BlockTemplateValidationException('block_template must be an object');
        }

        return self::normalizeTemplate($template);
    }

    /**
     * @param array<string, mixed> $template
     * @return array<string, mixed>
     */
    private static function normalizeTemplate(array $template): array
    {
        $blocks = $template['blocks'] ?? [];
        if (! is_array($blocks)) {
            throw new BlockTemplateValidationException('blocks must be an array');
        }

        if (isset($template['version']) && $template['version'] !== '1.0') {
            throw new BlockTemplateValidationException('version must be "1.0"');
        }

        $inputSortOrders = [];
        foreach ($blocks as $index => $block) {
            if (is_array($block) && isset($block['sort_order'])) {
                $so = $block['sort_order'];
                if (is_numeric($so)) {
                    $so = (int) $so;
                }
                if (is_int($so)) {
                    if (in_array($so, $inputSortOrders, true)) {
                        throw new BlockTemplateValidationException("Duplicate sort_order {$so}: each block must have a unique sort_order");
                    }
                    $inputSortOrders[] = $so;
                }
            }
        }

        $normalizedBlocks = [];
        foreach (array_values($blocks) as $index => $block) {
            if (! is_array($block)) {
                throw new BlockTemplateValidationException("Block at index {$index} must be an object");
            }

            $normalizedBlocks[] = self::normalizeBlock($block, $index);
        }

        if ($normalizedBlocks === []) {
            throw new BlockTemplateValidationException('blocks array must have at least one item');
        }

        $template = [
            'version' => '1.0',
            'blocks' => $normalizedBlocks,
        ];

        self::validateCanonical($template);

        return $template;
    }

    /**
     * @param array<string, mixed> $block
     * @return array<string, mixed>
     */
    private static function normalizeBlock(array $block, int $index): array
    {
        $blockKey = trim((string) ($block['block_key'] ?? ''));
        if ($blockKey === '') {
            throw new BlockTemplateValidationException("Block at index {$index}: block_key is required and must be a string");
        }

        if (! preg_match('/^[a-z][a-z0-9_]*$/', $blockKey)) {
            throw new BlockTemplateValidationException("Block at index {$index}: block_key must match ^[a-z][a-z0-9_]*$");
        }

        $normalized = [
            'block_key' => $blockKey,
            'sort_order' => $index + 1,
            'required' => self::normalizeBool($block['required'] ?? true, true),
            'locked' => self::normalizeBool($block['locked'] ?? false, false),
        ];

        $label = self::normalizeString($block['label'] ?? null, 100);
        if ($label !== null) {
            $normalized['label'] = $label;
        }

        $helpText = self::normalizeString($block['help_text'] ?? null, 500);
        if ($helpText !== null) {
            $normalized['help_text'] = $helpText;
        }

        $defaults = $block['block_config_defaults'] ?? [];
        if ($defaults === '') {
            $normalized['block_config_defaults'] = new \stdClass();
        } elseif (is_array($defaults) || is_object($defaults)) {
            $normalized['block_config_defaults'] = self::normalizeDefaults((array) $defaults);
        } else {
            throw new BlockTemplateValidationException("Block at index {$index}: block_config_defaults must be an object");
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $template
     */
    private static function validateCanonical(array $template): void
    {
        if ($template['version'] !== '1.0') {
            throw new BlockTemplateValidationException('version must be "1.0"');
        }

        if (! isset($template['blocks']) || ! is_array($template['blocks'])) {
            throw new BlockTemplateValidationException('blocks must be an array');
        }

        if (count($template['blocks']) > 50) {
            throw new BlockTemplateValidationException('blocks array must have at most 50 items');
        }

        $sortOrders = [];
        foreach ($template['blocks'] as $index => $block) {
            if (! is_array($block)) {
                throw new BlockTemplateValidationException("Block at index {$index} must be an object");
            }

            $sortOrder = $block['sort_order'] ?? null;
            if (! is_int($sortOrder)) {
                throw new BlockTemplateValidationException("Block at index {$index}: sort_order must be an integer");
            }

            if ($sortOrder < 1 || $sortOrder > 1000) {
                throw new BlockTemplateValidationException("Block at index {$index}: sort_order must be between 1 and 1000");
            }

            if (in_array($sortOrder, $sortOrders, true)) {
                throw new BlockTemplateValidationException("Duplicate sort_order {$sortOrder}: each block must have a unique sort_order");
            }
            $sortOrders[] = $sortOrder;

            if (! is_string($block['block_key'] ?? null) || $block['block_key'] === '') {
                throw new BlockTemplateValidationException("Block at index {$index}: block_key is required and must be a string");
            }

            if (isset($block['label']) && (! is_string($block['label']) || strlen($block['label']) > 100)) {
                throw new BlockTemplateValidationException("Block at index {$index}: label must be a string with at most 100 characters");
            }

            if (isset($block['help_text']) && (! is_string($block['help_text']) || strlen($block['help_text']) > 500)) {
                throw new BlockTemplateValidationException("Block at index {$index}: help_text must be a string with at most 500 characters");
            }

            if (! array_key_exists('required', $block) || ! is_bool($block['required'])) {
                throw new BlockTemplateValidationException("Block at index {$index}: required must be a boolean");
            }

            if (! array_key_exists('locked', $block) || ! is_bool($block['locked'])) {
                throw new BlockTemplateValidationException("Block at index {$index}: locked must be a boolean");
            }

            if (! array_key_exists('block_config_defaults', $block)) {
                throw new BlockTemplateValidationException("Block at index {$index}: block_config_defaults is required");
            }

            if (! is_array($block['block_config_defaults']) && ! is_object($block['block_config_defaults'])) {
                throw new BlockTemplateValidationException("Block at index {$index}: block_config_defaults must be an object");
            }
        }
    }

    /**
     * @param mixed $value
     */
    private static function normalizeBool(mixed $value, bool $default): bool
    {
        if ($value === null || $value === '') {
            return $default;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (int) $value !== 0;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));
            if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
                return true;
            }
            if (in_array($normalized, ['0', 'false', 'no', 'off'], true)) {
                return false;
            }
        }

        return (bool) $value;
    }

    /**
     * @param mixed $value
     */
    private static function normalizeString(mixed $value, int $maxLength): ?string
    {
        if ($value === null) {
            return null;
        }

        if (! is_scalar($value)) {
            throw new BlockTemplateValidationException('String fields must be scalar values');
        }

        $normalized = trim((string) $value);
        if ($normalized === '') {
            return null;
        }

        if (strlen($normalized) > $maxLength) {
            throw new BlockTemplateValidationException("String field must not exceed {$maxLength} characters");
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $defaults
     * @return array<string, mixed>|object
     */
    private static function normalizeDefaults(array $defaults): array|object
    {
        $normalized = [];
        foreach ($defaults as $key => $value) {
            $stringKey = trim((string) $key);
            if ($stringKey === '') {
                continue;
            }

            $normalized[$stringKey] = self::normalizeDefaultValue($value);
        }

        ksort($normalized);

        if ($normalized === []) {
            return new \stdClass();
        }

        return $normalized;
    }

    /**
     * @return mixed
     */
    private static function normalizeDefaultValue(mixed $value): mixed
    {
        if (is_array($value)) {
            $normalized = [];
            foreach ($value as $key => $item) {
                $normalized[(string) $key] = self::normalizeDefaultValue($item);
            }

            ksort($normalized);

            return $normalized;
        }

        if (is_object($value)) {
            return self::normalizeDefaultValue((array) $value);
        }

        if (is_int($value) || is_float($value) || is_bool($value) || $value === null || is_string($value)) {
            return $value;
        }

        throw new BlockTemplateValidationException('Default values must be scalar, null, object, or array values');
    }
}
