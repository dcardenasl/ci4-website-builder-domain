<?php

declare(strict_types=1);

namespace App\DTO\Request\Cms;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'SettingCreateRequest')]
readonly class SettingCreateRequestDTO extends BaseRequestDTO
{
    #[OA\Property(description: 'setting_key', type: 'string')]
    public string $settingKey;
    #[OA\Property(description: 'setting_value', type: 'string', nullable: true)]
    public ?string $settingValue;
    #[OA\Property(description: 'setting_type', type: 'string')]
    public string $settingType;
    #[OA\Property(description: 'input_type', type: 'string', example: 'text')]
    public string $inputType;
    #[OA\Property(description: 'options_json for select type: [{value, label}]', type: 'string', nullable: true)]
    public ?string $optionsJson;
    #[OA\Property(description: 'setting_group', type: 'string')]
    public string $settingGroup;
    #[OA\Property(description: 'is_translatable', type: 'boolean')]
    public bool $isTranslatable;
    #[OA\Property(description: 'is_required', type: 'boolean')]
    public bool $isRequired;
    #[OA\Property(description: 'is_readonly', type: 'boolean')]
    public bool $isReadonly;
    #[OA\Property(description: 'sort_order', type: 'integer')]
    public int $sortOrder;
    #[OA\Property(description: 'description', type: 'string', nullable: true)]
    public ?string $description;
    #[OA\Property(description: 'setting_meta', type: 'string', nullable: true)]
    public ?string $settingMeta;

    /**
     * @var array<array{language_id: int, setting_value?: string, label?: string, placeholder?: string, help_text?: string}>
     */
    #[OA\Property(description: 'translations', type: 'array', items: new OA\Items(type: 'object'))]
    public array $translations;

    public function rules(): array
    {
        return [
            'setting_key'     => 'required|string|max_length[100]|is_unique[cms_settings.setting_key]',
            'setting_value'   => 'permit_empty|string',
            'setting_meta'    => 'permit_empty|string',
            'setting_type'    => 'required|in_list[string,int,bool,json,file_id]',
            'input_type'      => 'permit_empty|in_list[text,textarea,richtext,url,email,phone,color,number,boolean,image,file,select,code,slug]',
            'options_json'    => 'permit_empty|string',
            'setting_group'   => 'permit_empty|string|max_length[50]',
            'is_translatable' => 'permit_empty|boolean_like',
            'is_required'     => 'permit_empty|boolean_like',
            'is_readonly'     => 'permit_empty|boolean_like',
            'sort_order'      => 'permit_empty|integer',
            'description'     => 'permit_empty|string|max_length[255]',
            'translations'    => 'permit_empty',
        ];
    }

    protected function map(array $data): void
    {
        $this->settingKey = (string) ($data['setting_key'] ?? '');
        $this->settingValue = isset($data['setting_value']) ? (string) $data['setting_value'] : null;
        $this->settingMeta = isset($data['setting_meta']) ? (string) $data['setting_meta'] : null;
        $this->settingType = (string) ($data['setting_type'] ?? 'string');
        $this->inputType = (string) ($data['input_type'] ?? 'text');
        $this->optionsJson = isset($data['options_json']) ? (string) $data['options_json'] : null;
        $this->settingGroup = (string) ($data['setting_group'] ?? 'general');
        $this->isTranslatable = filter_var($data['is_translatable'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $this->isRequired = filter_var($data['is_required'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $this->isReadonly = filter_var($data['is_readonly'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $this->sortOrder = isset($data['sort_order']) ? (int) $data['sort_order'] : 0;
        $this->description = isset($data['description']) ? (string) $data['description'] : null;
        $this->translations = $data['translations'] ?? [];
    }

    public function toArray(): array
    {
        return [
            'setting_key'     => $this->settingKey,
            'setting_value'   => $this->settingValue,
            'setting_meta'    => $this->settingMeta,
            'setting_type'    => $this->settingType,
            'input_type'      => $this->inputType,
            'options_json'    => $this->optionsJson,
            'setting_group'   => $this->settingGroup,
            'is_translatable' => $this->isTranslatable,
            'is_required'     => $this->isRequired,
            'is_readonly'     => $this->isReadonly,
            'sort_order'      => $this->sortOrder,
            'description'     => $this->description,
            'translations'    => $this->translations,
        ];
    }
}
