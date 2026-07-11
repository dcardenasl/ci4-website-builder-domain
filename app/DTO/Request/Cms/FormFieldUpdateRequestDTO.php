<?php

declare(strict_types=1);

namespace App\DTO\Request\Cms;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;

readonly class FormFieldUpdateRequestDTO extends BaseRequestDTO
{
    public ?string $field_type;
    public ?int    $display_order;
    public ?bool   $is_required;
    public ?bool   $is_active;
    /** @var list<string>|null */
    public ?array  $options;
    /** @var array<int|string, mixed> */
    public array   $translations;

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'field_type' => 'permit_empty|in_list[text,email,phone,textarea,select,radio,checkbox,date,number,url]',
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function map(array $data): void
    {
        $this->field_type    = array_key_exists('field_type', $data) && $data['field_type'] !== '' ? (string) $data['field_type'] : null;
        $this->display_order = array_key_exists('display_order', $data) ? (int) $data['display_order'] : null;
        $this->is_required   = array_key_exists('is_required', $data) ? (bool) $data['is_required'] : null;
        $this->is_active     = array_key_exists('is_active', $data) ? (bool) $data['is_active'] : null;
        $this->options       = array_key_exists('options', $data) ? FormFieldCreateRequestDTO::normalizeOptions($data['options']) : null;
        $this->translations  = is_array($data['translations'] ?? null) ? $data['translations'] : [];

        // Only enforceable when field_type is part of THIS submission — an update
        // that doesn't touch field_type can't know the row's current type here.
        if ($this->field_type !== null
            && in_array($this->field_type, FormFieldCreateRequestDTO::CHOICE_TYPES, true)
            && ($this->options === null || $this->options === [])
        ) {
            throw new \dcardenasl\Ci4ApiCore\Exceptions\ValidationException(
                lang('Api.validationFailed'),
                ['options' => lang('Forms.options_required_for_choice_type')]
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = ['translations' => $this->translations];
        if ($this->field_type !== null) {
            $result['field_type'] = $this->field_type;
        }
        if ($this->display_order !== null) {
            $result['display_order'] = $this->display_order;
        }
        if ($this->is_required !== null) {
            $result['is_required'] = $this->is_required;
        }
        if ($this->is_active !== null) {
            $result['is_active'] = $this->is_active;
        }
        if ($this->options !== null) {
            $result['options'] = $this->options;
        }
        return $result;
    }
}
