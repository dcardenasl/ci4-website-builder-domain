<?php

declare(strict_types=1);

namespace App\DTO\Request\Cms;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'SettingConnectionCreateRequest')]
readonly class SettingConnectionCreateRequestDTO extends BaseRequestDTO
{
    #[OA\Property(description: 'Type of the connected entity', example: 'block_type')]
    public string $entityType;

    #[OA\Property(description: 'Key of the connected entity (e.g. hero_banner, form_embed)', example: 'hero_banner')]
    public string $entityKey;

    #[OA\Property(description: 'Human-readable description of how this setting is used', nullable: true, example: 'Logo shown in hero banner')]
    public ?string $usageLabel;

    public function rules(): array
    {
        return [
            'entity_type'  => 'required|in_list[block_type,form,collection,module]',
            'entity_key'   => 'required|string|max_length[100]',
            'usage_label'  => 'permit_empty|string|max_length[255]',
        ];
    }

    protected function map(array $data): void
    {
        $this->entityType  = (string) ($data['entity_type'] ?? '');
        $this->entityKey   = (string) ($data['entity_key'] ?? '');
        $this->usageLabel  = isset($data['usage_label']) && $data['usage_label'] !== '' ? (string) $data['usage_label'] : null;
    }

    public function toArray(): array
    {
        return [
            'entity_type'  => $this->entityType,
            'entity_key'   => $this->entityKey,
            'usage_label'  => $this->usageLabel,
        ];
    }
}
