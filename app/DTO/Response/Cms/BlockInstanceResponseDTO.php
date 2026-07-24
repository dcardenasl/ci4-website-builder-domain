<?php

declare(strict_types=1);

namespace App\DTO\Response\Cms;

use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'BlockInstanceResponse',
    title: 'BlockInstance Response',
    required: ["id","block_id","owner_type","owner_id","sort_order","is_active"]
)]
final readonly class BlockInstanceResponseDTO implements DataTransferObjectInterface
{
    public function __construct(
        #[OA\Property(description: 'Unique identifier', example: 1)]
        public int $id,
        #[OA\Property(description: 'block_id', type: 'integer')]
        public int $block_id,
        #[OA\Property(description: 'owner_type', type: 'string')]
        public string $owner_type,
        #[OA\Property(description: 'owner_id', type: 'integer')]
        public int $owner_id,
        #[OA\Property(description: 'parent_instance_id', type: 'integer', nullable: true)]
        public ?int $parent_instance_id,
        #[OA\Property(description: 'sort_order', type: 'integer')]
        public int $sort_order,
        #[OA\Property(description: 'column_index', type: 'integer', nullable: true)]
        public ?int $column_index,
        #[OA\Property(description: 'is_active', type: 'boolean')]
        public bool $is_active,
        /**
         * @var array<string, mixed>|null
         */
        #[OA\Property(description: 'block_config', type: 'object', nullable: true)]
        public ?array $block_config,
        /**
         * @var array<array{language_id: int, block_data: array<string, mixed>, is_published: bool}>
         */
        #[OA\Property(description: 'translations', type: 'array', items: new OA\Items(type: 'object'))]
        public array $translations = [],
        #[OA\Property(property: 'created_at', description: 'Creation timestamp', example: '2026-02-26 12:00:00', nullable: true)]
        public ?string $createdAt = null,
        #[OA\Property(property: 'updated_at', description: 'Last update timestamp', example: '2026-02-26 12:00:00', nullable: true)]
        public ?string $updatedAt = null
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new static(
            id: (int) ($data['id'] ?? 0),
            block_id: (int) ($data['block_id'] ?? 0),
            owner_type: (string) ($data['owner_type'] ?? ''),
            owner_id: (int) ($data['owner_id'] ?? 0),
            parent_instance_id: isset($data['parent_instance_id']) ? (int) $data['parent_instance_id'] : null,
            sort_order: (int) ($data['sort_order'] ?? 0),
            column_index: isset($data['column_index']) ? (int) $data['column_index'] : null,
            is_active: (bool) ($data['is_active'] ?? false),
            block_config: isset($data['block_config']) ? (array) $data['block_config'] : null,
            translations: $data['translations'] ?? [],
            createdAt: DateValue::toString($data['created_at'] ?? null),
            updatedAt: DateValue::toString($data['updated_at'] ?? null),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'block_id' => $this->block_id,
            'owner_type' => $this->owner_type,
            'owner_id' => $this->owner_id,
            'parent_instance_id' => $this->parent_instance_id,
            'sort_order' => $this->sort_order,
            'column_index' => $this->column_index,
            'is_active' => $this->is_active,
            'block_config' => $this->block_config,
            'translations' => $this->translations,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
