<?php

declare(strict_types=1);

namespace App\DTO\Request\Cms;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;

readonly class FormFieldCreateRequestDTO extends BaseRequestDTO
{
    /** Field types whose values are picked from a fixed list and therefore require `options`. */
    public const CHOICE_TYPES = ['select', 'radio', 'checkbox'];

    public string $field_key;
    public string $field_type;
    public int    $display_order;
    public bool   $is_required;
    public bool   $is_active;
    /**
     * Stable, language-independent option values (e.g. "1-hora") — what a
     * submission actually stores. Display labels are per-language and live in
     * translations[*].option_labels, never here.
     *
     * @var list<string>|null
     */
    public ?array $options;
    /** @var array<int|string, mixed> */
    public array  $translations;

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'field_key'  => 'required|alpha_dash|max_length[100]',
            'field_type' => 'required|in_list[text,email,phone,textarea,select,radio,checkbox,date,number,url]',
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
        $this->options       = self::normalizeOptions($data['options'] ?? null);
        $this->translations  = is_array($data['translations'] ?? null) ? $data['translations'] : [];

        if (in_array($this->field_type, self::CHOICE_TYPES, true) && ($this->options === null || $this->options === [])) {
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
        return [
            'field_key'     => $this->field_key,
            'field_type'    => $this->field_type,
            'display_order' => $this->display_order,
            'is_required'   => $this->is_required,
            'is_active'     => $this->is_active,
            'options'       => $this->options,
            'translations'  => $this->translations,
        ];
    }

    /**
     * Keep only non-empty, de-duplicated option values, in submitted order.
     *
     * @return list<string>|null
     */
    public static function normalizeOptions(mixed $raw): ?array
    {
        if (! is_array($raw)) {
            return null;
        }

        $normalized = [];
        foreach ($raw as $option) {
            $value = trim(is_array($option) ? (string) ($option['value'] ?? '') : (string) $option);
            if ($value === '' || in_array($value, $normalized, true)) {
                continue;
            }
            $normalized[] = $value;
        }

        return $normalized;
    }
}
