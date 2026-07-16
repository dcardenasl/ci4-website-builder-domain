<?php

declare(strict_types=1);

namespace App\Libraries\Cms;

final class BlockSchemaIntrospector
{
    public function __construct(private readonly FieldPrimitiveRegistry $registry = new FieldPrimitiveRegistry())
    {
    }

    /**
     * @param array<string, mixed> $schema
     * @return array{
     *     contains_richtext: bool,
     *     contains_image: bool,
     *     contains_file: bool,
     *     required_fields: list<string>,
     *     translatable_fields: list<string>,
     *     unsupported_fields: list<string>,
     *     fields: array<string, array<string, mixed>>
     * }
     */
    public function introspect(array $schema): array
    {
        $fields = is_array($schema['fields'] ?? null) ? $schema['fields'] : [];
        $normalizedFields = [];
        $required = [];
        $translatable = [];
        $unsupported = [];

        foreach ($fields as $fieldKey => $definition) {
            if (! is_string($fieldKey)) {
                continue;
            }

            $fieldDefinition = is_array($definition) ? $definition : [];
            $primitive = $this->registry->normalize((string) ($fieldDefinition['type'] ?? 'string'), $fieldDefinition);

            $normalizedFields[$fieldKey] = $fieldDefinition + [
                'type' => (string) ($fieldDefinition['type'] ?? 'string'),
                'primitive' => $primitive,
            ];

            if ((bool) ($fieldDefinition['required'] ?? false)) {
                $required[] = $fieldKey;
            }

            if ($this->registry->isTranslatable($primitive)) {
                $translatable[] = $fieldKey;
            }

            if (! $this->registry->isSupported($primitive)) {
                $unsupported[] = $fieldKey;
            }
        }

        $primitives = array_column($normalizedFields, 'primitive');
        $containsMediaReference = in_array('media_reference', $primitives, true);

        return [
            'contains_richtext' => in_array('richtext', $primitives, true),
            'contains_image' => in_array('image', $primitives, true) || $containsMediaReference,
            'contains_file' => in_array('file', $primitives, true) || in_array('image', $primitives, true) || $containsMediaReference,
            'required_fields' => $required,
            'translatable_fields' => $translatable,
            'unsupported_fields' => $unsupported,
            'fields' => $normalizedFields,
        ];
    }
}
