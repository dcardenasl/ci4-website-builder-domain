<?php

declare(strict_types=1);

namespace App\DTO\Response\Cms;

use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SettingResponse',
    title: 'Setting Response',
    required: ["id", "setting_key", "setting_type", "input_type", "setting_group", "is_translatable"]
)]
readonly class SettingResponseDTO implements DataTransferObjectInterface
{
    /**
     * @param array<array{language_id: int, setting_value?: string, label?: string, placeholder?: string, help_text?: string}> $translations
     * @param array<array{value: string, label: string}>|null $optionsJson
     */
    public function __construct(
        #[OA\Property(description: 'Unique identifier', example: 1)]
        public int $id,
        #[OA\Property(description: 'Setting key', example: 'site_title')]
        public string $settingKey,
        #[OA\Property(description: 'Setting value', example: 'My Web Site', nullable: true)]
        public ?string $settingValue,
        #[OA\Property(description: 'Data type for storage and casting', example: 'string')]
        public string $settingType,
        #[OA\Property(description: 'UI control type for rendering the field', example: 'text')]
        public string $inputType,
        #[OA\Property(description: 'Setting group', example: 'general')]
        public string $settingGroup,
        #[OA\Property(description: 'Is translatable', example: true)]
        public bool $isTranslatable,
        #[OA\Property(description: 'Is required', example: false)]
        public bool $isRequired,
        #[OA\Property(description: 'Is readonly', example: false)]
        public bool $isReadonly,
        #[OA\Property(description: 'Sort order', example: 0)]
        public int $sortOrder,
        #[OA\Property(description: 'Description', example: 'Main site title', nullable: true)]
        public ?string $description = null,
        #[OA\Property(description: 'Options for select input_type', type: 'array', items: new OA\Items(type: 'object'), nullable: true)]
        public ?array $optionsJson = null,
        #[OA\Property(description: 'Translations list with value, label, placeholder, help_text per language', type: 'array', items: new OA\Items(type: 'object'))]
        public array $translations = [],
        #[OA\Property(property: 'created_at', description: 'Creation timestamp', example: '2026-02-26 12:00:00', nullable: true)]
        public ?string $createdAt = null,
        #[OA\Property(property: 'updated_at', description: 'Last update timestamp', example: '2026-02-26 12:00:00', nullable: true)]
        public ?string $updatedAt = null
    ) {
    }

    public function toArray(): array
    {
        return [
            'id'              => $this->id,
            'setting_key'     => $this->settingKey,
            'setting_value'   => $this->settingValue,
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
            'created_at'      => $this->createdAt,
            'updated_at'      => $this->updatedAt,
        ];
    }
}
