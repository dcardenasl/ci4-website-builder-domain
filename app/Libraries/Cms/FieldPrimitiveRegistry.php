<?php

declare(strict_types=1);

namespace App\Libraries\Cms;

final class FieldPrimitiveRegistry
{
    /** @var list<string> */
    private const SUPPORTED = [
        'text',
        'textarea',
        'richtext',
        'image',
        'file',
        'url',
        'number',
        'boolean',
        'select',
        'date',
        'datetime',
    ];

    /** @var array<string, string> */
    private const ALIASES = [
        'string' => 'text',
        'text' => 'textarea',
        'textarea' => 'textarea',
        'rich_text' => 'richtext',
        'rich-text' => 'richtext',
        'richtext' => 'richtext',
        'html' => 'richtext',
        'integer' => 'number',
        'int' => 'number',
        'float' => 'number',
        'decimal' => 'number',
        'bool' => 'boolean',
        'boolean' => 'boolean',
        'file' => 'file',
        'image' => 'image',
        'url' => 'url',
        'select' => 'select',
        'date' => 'date',
        'datetime' => 'datetime',
        'date_time' => 'datetime',
    ];

    /**
     * @return list<string>
     */
    public function supported(): array
    {
        return self::SUPPORTED;
    }

    /**
     * @param array<string, mixed> $fieldDefinition
     */
    public function normalize(string $type, array $fieldDefinition = []): string
    {
        $normalized = self::ALIASES[strtolower(trim($type))] ?? '';

        if ($normalized === 'file' && $this->acceptsImage($fieldDefinition)) {
            return 'image';
        }

        return in_array($normalized, self::SUPPORTED, true) ? $normalized : 'unsupported';
    }

    public function isSupported(string $primitive): bool
    {
        return in_array($primitive, self::SUPPORTED, true);
    }

    /**
     * Fields whose values are naturally language-specific.
     */
    public function isTranslatable(string $primitive): bool
    {
        return in_array($primitive, ['text', 'textarea', 'richtext', 'url'], true);
    }

    /**
     * @param array<string, mixed> $fieldDefinition
     */
    private function acceptsImage(array $fieldDefinition): bool
    {
        $accept = strtolower((string) ($fieldDefinition['accept'] ?? $fieldDefinition['mime'] ?? $fieldDefinition['mime_type'] ?? ''));

        return $accept === 'image'
            || str_contains($accept, 'image/')
            || str_contains($accept, 'image/*');
    }
}
