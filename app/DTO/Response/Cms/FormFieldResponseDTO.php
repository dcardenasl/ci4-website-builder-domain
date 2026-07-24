<?php

declare(strict_types=1);

namespace App\DTO\Response\Cms;

use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;

final readonly class FormFieldResponseDTO implements DataTransferObjectInterface
{
    /** @var list<array<string, mixed>> */
    public array $translations;
    /**
     * Stable, language-independent option values. Display labels live per-
     * language inside each translations[] entry's `option_labels` map.
     *
     * @var list<string>
     */
    public array $options;

    /**
     * @param list<array<string, mixed>> $translations
     * @param list<string>|null          $options
     */
    public function __construct(
        public int    $id,
        public int    $form_id,
        public string $field_key,
        public string $field_type,
        public int    $display_order,
        public bool   $is_required,
        public bool   $is_active,
        public string $created_at,
        public string $updated_at,
        array         $translations = [],
        ?array        $options = null,
    ) {
        $this->translations = $translations;
        $this->options       = $options ?? [];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id:            (int) ($data['id'] ?? 0),
            form_id:       (int) ($data['form_id'] ?? 0),
            field_key:     (string) ($data['field_key'] ?? ''),
            field_type:    (string) ($data['field_type'] ?? 'text'),
            display_order: (int) ($data['display_order'] ?? 0),
            is_required:   (bool) ($data['is_required'] ?? false),
            is_active:     (bool) ($data['is_active'] ?? true),
            created_at:    (string) ($data['created_at'] ?? ''),
            updated_at:    (string) ($data['updated_at'] ?? ''),
            translations:  is_array($data['translations'] ?? null) ? array_values($data['translations']) : [],
            options:       is_array($data['options'] ?? null) ? array_values($data['options']) : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id'            => $this->id,
            'form_id'       => $this->form_id,
            'field_key'     => $this->field_key,
            'field_type'    => $this->field_type,
            'display_order' => $this->display_order,
            'is_required'   => $this->is_required,
            'is_active'     => $this->is_active,
            'options'       => $this->options,
            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,
            'translations'  => $this->translations,
        ];
    }
}
