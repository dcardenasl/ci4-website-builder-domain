<?php

declare(strict_types=1);

namespace App\DTO\Request\Cms;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;

readonly class FormFieldCreateRequestDTO extends BaseRequestDTO
{
    public string $field_key;
    public string $field_type;
    public int    $display_order;
    public bool   $is_required;
    public bool   $is_active;
    /** @var array<int|string, mixed> */
    public array  $translations;

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'field_key'  => 'required|alpha_dash|max_length[100]',
            'field_type' => 'required|in_list[text,email,phone,textarea]',
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function map(array $data): void
    {
        $this->field_key     = (string) ($data['field_key'] ?? '');
        $this->field_type    = (string) ($data['field_type'] ?? 'text');
        $this->display_order = (int) ($data['display_order'] ?? 0);
        $this->is_required   = (bool) ($data['is_required'] ?? false);
        $this->is_active     = (bool) ($data['is_active'] ?? true);
        $this->translations  = is_array($data['translations'] ?? null) ? $data['translations'] : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'field_key'     => $this->field_key,
            'field_type'    => $this->field_type,
            'display_order' => $this->display_order,
            'is_required'   => $this->is_required,
            'is_active'     => $this->is_active,
            'translations'  => $this->translations,
        ];
    }
}
