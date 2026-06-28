<?php

declare(strict_types=1);

namespace App\DTO\Response\Cms;

use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'SettingConnectionResponse', title: 'Setting Connection Response')]
readonly class SettingConnectionResponseDTO implements DataTransferObjectInterface
{
    public function __construct(
        #[OA\Property(description: 'Unique identifier', example: 1)]
        public int $id,
        #[OA\Property(description: 'Setting ID this connection belongs to', example: 5)]
        public int $settingId,
        #[OA\Property(description: 'Type of entity this setting is connected to', example: 'block_type')]
        public string $entityType,
        #[OA\Property(description: 'Key of the connected entity', example: 'hero_banner')]
        public string $entityKey,
        #[OA\Property(description: 'Human-readable description of how this setting is used', nullable: true)]
        public ?string $usageLabel = null,
        #[OA\Property(property: 'created_at', description: 'Creation timestamp', nullable: true)]
        public ?string $createdAt = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'setting_id'  => $this->settingId,
            'entity_type' => $this->entityType,
            'entity_key'  => $this->entityKey,
            'usage_label' => $this->usageLabel,
            'created_at'  => $this->createdAt,
        ];
    }
}
