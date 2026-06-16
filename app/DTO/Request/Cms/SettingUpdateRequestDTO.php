<?php

declare(strict_types=1);

namespace App\DTO\Request\Cms;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'SettingUpdateRequest')]
readonly class SettingUpdateRequestDTO extends BaseRequestDTO
{
    #[OA\Property(description: 'id', type: 'integer', nullable: true)]
    public ?int $id;
    #[OA\Property(description: 'setting_key', type: 'string')]
    public ?string $settingKey;
    #[OA\Property(description: 'setting_value', type: 'string', nullable: true)]
    public ?string $settingValue;
    #[OA\Property(description: 'setting_type', type: 'string')]
    public ?string $settingType;
    #[OA\Property(description: 'setting_group', type: 'string')]
    public ?string $settingGroup;
    #[OA\Property(description: 'is_translatable', type: 'boolean')]
    public ?bool $isTranslatable;
    #[OA\Property(description: 'sort_order', type: 'integer')]
    public ?int $sortOrder;
    #[OA\Property(description: 'description', type: 'string', nullable: true)]
    public ?string $description;

    /**
     * @var array<array{language_id: int, setting_value: string}>|null
     */
    #[OA\Property(description: 'translations', type: 'array', items: new OA\Items(type: 'object'), nullable: true)]
    public ?array $translations;

    private array $mappedFields;

    public function rules(): array
    {
        return [
            'setting_key'              => 'permit_empty|string|max_length[100]',
            'setting_value'            => 'permit_empty|string',
            'setting_type'             => 'permit_empty|in_list[string,int,bool,json,file_id]',
            'setting_group'            => 'permit_empty|string|max_length[50]',
            'is_translatable'          => 'permit_empty|boolean_like',
            'sort_order'               => 'permit_empty|integer',
            'description'              => 'permit_empty|string|max_length[255]',
            'translations' => 'permit_empty',
        ];
    }

    protected function map(array $data): void
    {
        $mappedFields = [];

        if (array_key_exists('id', $data)) {
            $this->id = $data['id'] !== null ? (int) $data['id'] : null;
            $mappedFields['id'] = $this->id;
        } else {
            $this->id = null;
        }

        if (array_key_exists('setting_key', $data)) {
            $this->settingKey = (string) $data['setting_key'];
            $mappedFields['setting_key'] = $this->settingKey;
        } else {
            $this->settingKey = null;
        }

        if (array_key_exists('setting_value', $data)) {
            $this->settingValue = $data['setting_value'] !== null ? (string) $data['setting_value'] : null;
            $mappedFields['setting_value'] = $this->settingValue;
        } else {
            $this->settingValue = null;
        }

        if (array_key_exists('setting_type', $data)) {
            $this->settingType = (string) $data['setting_type'];
            $mappedFields['setting_type'] = $this->settingType;
        } else {
            $this->settingType = null;
        }

        if (array_key_exists('setting_group', $data)) {
            $this->settingGroup = (string) $data['setting_group'];
            $mappedFields['setting_group'] = $this->settingGroup;
        } else {
            $this->settingGroup = null;
        }

        if (array_key_exists('is_translatable', $data)) {
            $this->isTranslatable = filter_var($data['is_translatable'], FILTER_VALIDATE_BOOLEAN);
            $mappedFields['is_translatable'] = $this->isTranslatable;
        } else {
            $this->isTranslatable = null;
        }

        if (array_key_exists('sort_order', $data)) {
            $this->sortOrder = (int) $data['sort_order'];
            $mappedFields['sort_order'] = $this->sortOrder;
        } else {
            $this->sortOrder = null;
        }

        if (array_key_exists('description', $data)) {
            $this->description = $data['description'] !== null ? (string) $data['description'] : null;
            $mappedFields['description'] = $this->description;
        } else {
            $this->description = null;
        }

        if (array_key_exists('translations', $data)) {
            $this->translations = (array) $data['translations'];
            $mappedFields['translations'] = $this->translations;
        } else {
            $this->translations = null;
        }

        $this->mappedFields = $mappedFields;
    }

    public function toArray(): array
    {
        return $this->mappedFields;
    }
}
