<?php

declare(strict_types=1);

namespace App\DTO\Response\Cms;

use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CategoryResponse',
    title: 'Category Response',
    required: ["id","collection_id","sort_order","is_active"]
)]
final readonly class CategoryResponseDTO implements DataTransferObjectInterface
{
    public function __construct(
        #[OA\Property(description: 'Unique identifier', example: 1)]
        public int $id,
        #[OA\Property(description: 'collection_id', type: 'integer')]
        public int $collection_id,
        #[OA\Property(description: 'parent_id', type: 'integer', nullable: true)]
        public ?int $parent_id,
        #[OA\Property(description: 'sort_order', type: 'integer')]
        public int $sort_order,
        #[OA\Property(description: 'is_active', type: 'boolean')]
        public bool $is_active,
        #[OA\Property(description: 'collection_name', type: 'string', nullable: true)]
        public ?string $collection_name = null,
        #[OA\Property(description: 'parent_label', type: 'string', nullable: true)]
        public ?string $parent_label = null,
        #[OA\Property(property: 'created_at', description: 'Creation timestamp', example: '2026-02-26 12:00:00', nullable: true)]
        public ?string $createdAt = null,
        #[OA\Property(property: 'updated_at', description: 'Last update timestamp', example: '2026-02-26 12:00:00', nullable: true)]
        public ?string $updatedAt = null,
        #[OA\Property(description: 'name', type: 'string', nullable: true)]
        public ?string $name = null,
        #[OA\Property(description: 'slug', type: 'string', nullable: true)]
        public ?string $slug = null,
        /** @var array<string, mixed>|null */
        #[OA\Property(description: 'translations', type: 'array', items: new OA\Items(type: 'object'), nullable: true)]
        public ?array $translations = null
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new static(
            id: (int) ($data['id'] ?? 0),
            collection_id: (int) ($data['collection_id'] ?? 0),
            parent_id: isset($data['parent_id']) ? (int) $data['parent_id'] : null,
            sort_order: (int) ($data['sort_order'] ?? 0),
            is_active: (bool) ($data['is_active'] ?? false),
            collection_name: $data['collection_name'] ?? null,
            parent_label: $data['parent_label'] ?? null,
            createdAt: DateValue::toString($data['created_at'] ?? null),
            updatedAt: DateValue::toString($data['updated_at'] ?? null),
            name: $data['name'] ?? null,
            slug: $data['slug'] ?? null,
            translations: $data['translations'] ?? null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'collection_id' => $this->collection_id,
            'collection_name' => $this->collection_name,
            'name' => $this->name,
            'slug' => $this->slug,
            'translations' => $this->translations,
            'parent_id' => $this->parent_id,
            'parent_label' => $this->parent_label,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
