<?php

declare(strict_types=1);

namespace App\DTO\Response\Cms;

use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'EntryResponse',
    title: 'Entry Response',
    required: ["id","collection_id","workflow_status","is_featured","view_count","sort_order","is_in_sitemap"]
)]
final readonly class EntryResponseDTO implements DataTransferObjectInterface
{
    /**
     * @param array<mixed>|null $translations
     * @param list<array<string, mixed>>|null $categories
     * @param list<array<string, mixed>>|null $tags
     */
    public function __construct(
        #[OA\Property(description: 'Unique identifier', example: 1)]
        public int $id,
        #[OA\Property(description: 'collection_id', type: 'integer')]
        public int $collection_id,
        #[OA\Property(description: 'author_id', type: 'integer', nullable: true)]
        public ?int $author_id,
        #[OA\Property(description: 'workflow_status', type: 'string')]
        public string $workflow_status,
        #[OA\Property(description: 'published_at', type: 'string', format: 'date-time', nullable: true)]
        public ?string $published_at,
        #[OA\Property(description: 'scheduled_at', type: 'string', format: 'date-time', nullable: true)]
        public ?string $scheduled_at,
        #[OA\Property(description: 'is_featured', type: 'boolean')]
        public bool $is_featured,
        #[OA\Property(description: 'view_count', type: 'integer')]
        public int $view_count,
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
        public ?array $translations = null,
        #[OA\Property(property: 'categories', type: 'array', items: new OA\Items(type: 'object'), nullable: true)]
        public ?array $categories = null,
        #[OA\Property(property: 'tags', type: 'array', items: new OA\Items(type: 'object'), nullable: true)]
        public ?array $tags = null,
        #[OA\Property(description: 'title', type: 'string', nullable: true)]
        public ?string $title = null,
        #[OA\Property(description: 'slug', type: 'string', nullable: true)]
        public ?string $slug = null,
        #[OA\Property(description: 'collection_key', type: 'string', nullable: true)]
        public ?string $collection_key = null
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
            author_id: isset($data['author_id']) ? (int) $data['author_id'] : null,
            workflow_status: (string) ($data['workflow_status'] ?? ''),
            published_at: $data['published_at'] ?? null,
            scheduled_at: $data['scheduled_at'] ?? null,
            is_featured: (bool) ($data['is_featured'] ?? false),
            view_count: (int) ($data['view_count'] ?? 0),
            sort_order: (int) ($data['sort_order'] ?? 0),
            sitemap_priority: isset($data['sitemap_priority']) ? (float) $data['sitemap_priority'] : null,
            sitemap_changefreq: $data['sitemap_changefreq'] ?? null,
            is_in_sitemap: (bool) ($data['is_in_sitemap'] ?? false),
            createdAt: DateValue::toString($data['created_at'] ?? null),
            updatedAt: DateValue::toString($data['updated_at'] ?? null),
            translations: $data['translations'] ?? null,
            categories: $data['categories'] ?? null,
            tags: $data['tags'] ?? null,
            title: $data['title'] ?? null,
            slug: $data['slug'] ?? null,
            collection_key: $data['collection_key'] ?? null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $res = [
            'id' => $this->id,
            'collection_id' => $this->collection_id,
            'title' => $this->title,
            'slug' => $this->slug,
            'author_id' => $this->author_id,
            'workflow_status' => $this->workflow_status,
            'published_at' => $this->published_at,
            'scheduled_at' => $this->scheduled_at,
            'is_featured' => $this->is_featured,
            'view_count' => $this->view_count,
            'sort_order' => $this->sort_order,
            'sitemap_priority' => $this->sitemap_priority,
            'sitemap_changefreq' => $this->sitemap_changefreq,
            'is_in_sitemap' => $this->is_in_sitemap,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];

        if ($this->collection_key !== null) {
            $res['collection_key'] = $this->collection_key;
        }

        if ($this->translations !== null) {
            $res['translations'] = $this->translations;
        }

        if ($this->categories !== null) {
            $res['categories'] = $this->categories;
        }

        if ($this->tags !== null) {
            $res['tags'] = $this->tags;
        }

        return $res;
    }
}
