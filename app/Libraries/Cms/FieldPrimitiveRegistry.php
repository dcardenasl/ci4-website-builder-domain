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
        'media_reference',
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
        'media_reference' => 'media_reference',
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

    public function normalize(string $type): string
    {
        $normalized = self::ALIASES[strtolower(trim($type))] ?? '';

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
}
