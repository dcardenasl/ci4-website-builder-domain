<?php

declare(strict_types=1);

namespace App\DTO\Response\Cms;

use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PageResponse',
    title: 'Page Response',
    required: ["id","page_type","status","sort_order","is_in_sitemap"]
)]
final readonly class PageResponseDTO implements DataTransferObjectInterface
{
    /**
     * @param array<mixed>|null $translations
     */
    public function __construct(
        #[OA\Property(description: 'Unique identifier', example: 1)]
        public int $id,
        #[OA\Property(description: 'parent_id', type: 'integer', nullable: true)]
        public ?int $parent_id,
        #[OA\Property(description: 'collection_id', type: 'integer', nullable: true)]
        public ?int $collection_id,
        #[OA\Property(description: 'page_type', type: 'string')]
        public string $page_type,
        #[OA\Property(description: 'status', type: 'string')]
        public string $status,
        #[OA\Property(description: 'published_at', type: 'string', format: 'date-time', nullable: true)]
        public ?string $published_at,
        #[OA\Property(description: 'scheduled_at', type: 'string', format: 'date-time', nullable: true)]
        public ?string $scheduled_at,
        #[OA\Property(description: 'sort_order', type: 'integer')]
        public int $sort_order,
        #[OA\Property(description: 'sitemap_priority', type: 'number', format: 'float', nullable: true)]
        public ?float $sitemap_priority,
        #[OA\Property(description: 'sitemap_changefreq', type: 'string', nullable: true)]
        public ?string $sitemap_changefreq,
        #[OA\Property(description: 'is_in_sitemap', type: 'boolean')]
        public bool $is_in_sitemap,
        #[OA\Property(property: 'created_at', description: 'Creation timestamp', example: '2026-02-26 12:00:00', nullable: true)]
        public ?string $createdAt = null,
        #[OA\Property(property: 'updated_at', description: 'Last update timestamp', example: '2026-02-26 12:00:00', nullable: true)]
        public ?string $updatedAt = null,
        #[OA\Property(property: 'translations', type: 'array', items: new OA\Items(type: 'object'), nullable: true)]
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
            parent_id: isset($data['parent_id']) ? (int) $data['parent_id'] : null,
            collection_id: isset($data['collection_id']) ? (int) $data['collection_id'] : null,
            page_type: (string) ($data['page_type'] ?? ''),
            status: (string) ($data['status'] ?? ''),
            published_at: $data['published_at'] ?? null,
            scheduled_at: $data['scheduled_at'] ?? null,
            sort_order: (int) ($data['sort_order'] ?? 0),
            sitemap_priority: isset($data['sitemap_priority']) ? (float) $data['sitemap_priority'] : null,
            sitemap_changefreq: $data['sitemap_changefreq'] ?? null,
            is_in_sitemap: (bool) ($data['is_in_sitemap'] ?? false),
            createdAt: DateValue::toString($data['created_at'] ?? null),
            updatedAt: DateValue::toString($data['updated_at'] ?? null),
            translations: $data['translations'] ?? null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $res = [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'collection_id' => $this->collection_id,
            'page_type' => $this->page_type,
            'status' => $this->status,
            'published_at' => $this->published_at,
            'scheduled_at' => $this->scheduled_at,
            'sort_order' => $this->sort_order,
            'sitemap_priority' => $this->sitemap_priority,
            'sitemap_changefreq' => $this->sitemap_changefreq,
            'is_in_sitemap' => $this->is_in_sitemap,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];

        if ($this->translations !== null) {
            $res['translations'] = $this->translations;
        }

        return $res;
    }
}
