<?php

declare(strict_types=1);

namespace App\DTO\Request\Cms;

use App\Libraries\Cms\CmsEnums;
use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'CollectionCreateRequest')]
readonly class CollectionCreateRequestDTO extends BaseRequestDTO
{
    #[OA\Property(description: 'collection_key', type: 'string')]
    public string $collection_key;
    #[OA\Property(description: 'url_prefix', type: 'string')]
    public string $url_prefix;
    #[OA\Property(description: 'is_active', type: 'boolean')]
    public bool $is_active;
    #[OA\Property(description: 'requires_approval', type: 'boolean')]
    public bool $requires_approval;
    #[OA\Property(description: 'enables_categories', type: 'boolean')]
    public bool $enables_categories;
    #[OA\Property(description: 'enables_tags', type: 'boolean')]
    public bool $enables_tags;
    #[OA\Property(description: 'default_sitemap_priority', type: 'number', format: 'float', nullable: true)]
    public ?float $default_sitemap_priority;
    #[OA\Property(description: 'default_changefreq', type: 'string', nullable: true, enum: ['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'])]
    public ?string $default_changefreq;
    #[OA\Property(description: 'sort_order', type: 'integer')]
    public int $sort_order;

    /**
     * @var array<array{language_id: int, name: string, description?: string, listing_title?: string, listing_intro?: string, default_meta_title?: string, default_meta_description?: string}>
     */
    #[OA\Property(description: 'translations', type: 'array', items: new OA\Items(type: 'object'))]
    public array $translations;

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'collection_key' => 'required|string|max_length[50]|is_unique[cms_collections.collection_key]',
            'url_prefix' => 'required|string|max_length[150]|is_unique[cms_collections.url_prefix]',
            'is_active' => 'permit_empty|boolean_like',
            'requires_approval' => 'permit_empty|boolean_like',
            'enables_categories' => 'permit_empty|boolean_like',
            'enables_tags' => 'permit_empty|boolean_like',
            'default_sitemap_priority' => 'permit_empty|decimal',
            'default_changefreq' => 'permit_empty|' . CmsEnums::inListRule(CmsEnums::SITEMAP_CHANGEFREQ),
            'sort_order' => 'required|integer',
            'translations' => 'permit_empty',
            'translations.*.language_id' => 'required_with[translations]|is_natural_no_zero',
            'translations.*.name' => 'required_with[translations]|string|max_length[150]',
            'translations.*.description' => 'permit_empty|string',
            'translations.*.listing_title' => 'permit_empty|string|max_length[255]',
            'translations.*.listing_intro' => 'permit_empty|string',
            'translations.*.default_meta_title' => 'permit_empty|string|max_length[255]',
            'translations.*.default_meta_description' => 'permit_empty|string|max_length[500]',
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function map(array $data): void
    {
        $this->collection_key = (string) ($data['collection_key'] ?? '');
        $this->url_prefix = (string) ($data['url_prefix'] ?? '');
        $this->is_active = (bool) ($data['is_active'] ?? false);
        $this->requires_approval = (bool) ($data['requires_approval'] ?? false);
        $this->enables_categories = (bool) ($data['enables_categories'] ?? false);
        $this->enables_tags = (bool) ($data['enables_tags'] ?? false);
        $this->default_sitemap_priority = isset($data['default_sitemap_priority']) ? (float) $data['default_sitemap_priority'] : null;
        $this->default_changefreq = $data['default_changefreq'] ?? null;
        $this->sort_order = (int) ($data['sort_order'] ?? 0);
        $this->translations = $data['translations'] ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'collection_key' => $this->collection_key,
            'url_prefix' => $this->url_prefix,
            'is_active' => $this->is_active,
            'requires_approval' => $this->requires_approval,
            'enables_categories' => $this->enables_categories,
            'enables_tags' => $this->enables_tags,
            'default_sitemap_priority' => $this->default_sitemap_priority,
            'default_changefreq' => $this->default_changefreq,
            'sort_order' => $this->sort_order,
            'translations' => $this->translations,
        ];
    }
}
