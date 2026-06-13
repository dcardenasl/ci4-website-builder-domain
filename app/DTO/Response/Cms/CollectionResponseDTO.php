<?php

declare(strict_types=1);

namespace App\DTO\Response\Cms;

use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CollectionResponse',
    title: 'Collection Response',
    required: ["id","collection_key","url_prefix","is_active","requires_approval","enables_categories","enables_tags","sort_order"]
)]
final readonly class CollectionResponseDTO implements DataTransferObjectInterface
{
    public function __construct(
        #[OA\Property(description: 'Unique identifier', example: 1)]
        public int $id,
        #[OA\Property(description: 'collection_key', type: 'string')]
        public string $collection_key,
        #[OA\Property(description: 'url_prefix', type: 'string')]
        public string $url_prefix,
        #[OA\Property(description: 'is_active', type: 'boolean')]
        public bool $is_active,
        #[OA\Property(description: 'requires_approval', type: 'boolean')]
        public bool $requires_approval,
        #[OA\Property(description: 'enables_categories', type: 'boolean')]
        public bool $enables_categories,
        #[OA\Property(description: 'enables_tags', type: 'boolean')]
        public bool $enables_tags,
        #[OA\Property(description: 'default_sitemap_priority', type: 'number', format: 'float', nullable: true)]
        public ?float $default_sitemap_priority,
        #[OA\Property(description: 'default_changefreq', type: 'string', nullable: true)]
        public ?string $default_changefreq,
        #[OA\Property(description: 'sort_order', type: 'integer')]
        public int $sort_order,
        #[OA\Property(property: 'created_at', description: 'Creation timestamp', example: '2026-02-26 12:00:00', nullable: true)]
        public ?string $createdAt = null,
        #[OA\Property(property: 'updated_at', description: 'Last update timestamp', example: '2026-02-26 12:00:00', nullable: true)]
        public ?string $updatedAt = null,
        #[OA\Property(description: 'Collection translations', type: 'array', items: new OA\Items(type: 'object'))]
        public array $translations = []
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new static(
            id: (int) ($data['id'] ?? 0),
            collection_key: (string) ($data['collection_key'] ?? ''),
            url_prefix: (string) ($data['url_prefix'] ?? ''),
            is_active: (bool) ($data['is_active'] ?? false),
            requires_approval: (bool) ($data['requires_approval'] ?? false),
            enables_categories: (bool) ($data['enables_categories'] ?? false),
            enables_tags: (bool) ($data['enables_tags'] ?? false),
            default_sitemap_priority: isset($data['default_sitemap_priority']) ? (float) $data['default_sitemap_priority'] : null,
            default_changefreq: $data['default_changefreq'] ?? null,
            sort_order: (int) ($data['sort_order'] ?? 0),
            createdAt: DateValue::toString($data['created_at'] ?? null),
            updatedAt: DateValue::toString($data['updated_at'] ?? null),
            translations: $data['translations'] ?? []
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'collection_key' => $this->collection_key,
            'url_prefix' => $this->url_prefix,
            'is_active' => $this->is_active,
            'requires_approval' => $this->requires_approval,
            'enables_categories' => $this->enables_categories,
            'enables_tags' => $this->enables_tags,
            'default_sitemap_priority' => $this->default_sitemap_priority,
            'default_changefreq' => $this->default_changefreq,
            'sort_order' => $this->sort_order,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'translations' => $this->translations,
        ];
    }
}
