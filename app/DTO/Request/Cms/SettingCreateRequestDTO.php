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
    #[OA\Property(description: 'setting_group', type: 'string')]
    public string $settingGroup;
    #[OA\Property(description: 'is_translatable', type: 'boolean')]
    public bool $isTranslatable;
    #[OA\Property(description: 'sort_order', type: 'integer')]
    public int $sortOrder;
    #[OA\Property(description: 'description', type: 'string', nullable: true)]
    public ?string $description;

    /**
     * @var array<array{language_id: int, setting_value: string}>
     */
    #[OA\Property(description: 'translations', type: 'array', items: new OA\Items(type: 'object'))]
    public array $translations;

    public function rules(): array
    {
        return [
            'setting_key'              => 'required|string|max_length[100]|is_unique[cms_settings.setting_key]',
            'setting_value'            => 'permit_empty|string',
            'setting_type'             => 'required|in_list[string,int,bool,json,file_id]',
            'setting_group'            => 'permit_empty|string|max_length[50]',
            'is_translatable'          => 'permit_empty|boolean_like',
            'sort_order'               => 'permit_empty|integer',
            'description'              => 'permit_empty|string|max_length[255]',
            'translations' => 'permit_empty',
        ];
    }

    protected function map(array $data): void
    {
        $this->settingKey = (string) ($data['setting_key'] ?? '');
        $this->settingValue = isset($data['setting_value']) ? (string) $data['setting_value'] : null;
        $this->settingType = (string) ($data['setting_type'] ?? 'string');
        $this->settingGroup = (string) ($data['setting_group'] ?? 'general');
        $this->isTranslatable = filter_var($data['is_translatable'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $this->sortOrder = isset($data['sort_order']) ? (int) $data['sort_order'] : 0;
        $this->description = isset($data['description']) ? (string) $data['description'] : null;
        $this->translations = $data['translations'] ?? [];
    }

    public function toArray(): array
    {
        return [
            'setting_key'     => $this->settingKey,
            'setting_value'   => $this->settingValue,
            'setting_type'    => $this->settingType,
            'setting_group'   => $this->settingGroup,
            'is_translatable' => $this->isTranslatable,
            'sort_order'      => $this->sortOrder,
            'description'     => $this->description,
            'translations'    => $this->translations,
        ];
    }
}
