<?php

declare(strict_types=1);

namespace App\DTO\Request\Cms;

use App\Libraries\Cms\CmsEnums;
use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'PageCreateRequest')]
readonly class PageCreateRequestDTO extends BaseRequestDTO
{
    #[OA\Property(description: 'parent_id', type: 'integer', nullable: true)]
    public ?int $parent_id;
    #[OA\Property(description: 'collection_id', type: 'integer', nullable: true)]
    public ?int $collection_id;
    #[OA\Property(description: 'page_type', type: 'string', enum: ['home', 'generic', 'contact', 'privacy', 'terms', '404', '500', 'maintenance', 'collection_index'])]
    public string $page_type;
    #[OA\Property(description: 'status', type: 'string', nullable: true, enum: ['draft', 'published', 'archived'])]
    public string $status;
    #[OA\Property(description: 'published_at', type: 'string', format: 'date-time', nullable: true)]
    public ?string $published_at;
    #[OA\Property(description: 'scheduled_at', type: 'string', format: 'date-time', nullable: true)]
    public ?string $scheduled_at;
    #[OA\Property(description: 'sort_order', type: 'integer')]
    public int $sort_order;
    #[OA\Property(description: 'sitemap_priority', type: 'number', format: 'float', nullable: true)]
    public ?float $sitemap_priority;
    #[OA\Property(description: 'sitemap_changefreq', type: 'string', nullable: true, enum: ['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'])]
    public ?string $sitemap_changefreq;
    #[OA\Property(description: 'is_in_sitemap', type: 'boolean')]
    public bool $is_in_sitemap;

    /**
     * @var array<array{language_id: int, slug: string, title: string, excerpt?: string, meta_title?: string, meta_description?: string, og_image?: array{source_kind?: string, file_id?: int|null, url?: string|null}, og_image_file_id?: int|null, og_image_url?: string, og_type?: string, canonical_url?: string, robots?: string, schema_data?: array<mixed>}>
     */
    #[OA\Property(description: 'translations', type: 'array', items: new OA\Items(type: 'object'))]
    public array $translations;

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'parent_id' => 'permit_empty|is_natural_no_zero',
            'collection_id' => 'permit_empty|is_natural_no_zero',
            'page_type' => 'permit_empty|' . CmsEnums::inListRule(CmsEnums::PAGE_TYPE),
            'status' => 'permit_empty|' . CmsEnums::inListRule(CmsEnums::PAGE_STATUS),
            'published_at' => 'permit_empty|valid_date',
            'scheduled_at' => 'permit_empty|valid_date',
            'sort_order' => 'permit_empty|integer',
            'sitemap_priority' => 'permit_empty|decimal',
            'sitemap_changefreq' => 'permit_empty|' . CmsEnums::inListRule(CmsEnums::SITEMAP_CHANGEFREQ),
            'is_in_sitemap' => 'permit_empty|boolean_like',
            'translations' => 'permit_empty',
            'translations.*.language_id' => 'required_with[translations]|is_natural_no_zero',
            'translations.*.slug' => 'required_with[translations]|string|max_length[150]',
            'translations.*.title' => 'required_with[translations]|string|max_length[255]',
            'translations.*.excerpt' => 'permit_empty|string|max_length[500]',
            'translations.*.meta_title' => 'permit_empty|string|max_length[255]',
            'translations.*.meta_description' => 'permit_empty|string|max_length[500]',
            'translations.*.og_image' => 'permit_empty',
            'translations.*.og_image_file_id' => 'permit_empty|integer',
            'translations.*.og_image_url' => 'permit_empty|string|max_length[2048]',
            'translations.*.og_type' => 'permit_empty|string|max_length[50]',
            'translations.*.canonical_url' => 'permit_empty|string|max_length[500]',
            'translations.*.robots' => 'permit_empty|string|max_length[100]',
            'translations.*.schema_data' => 'permit_empty',
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function map(array $data): void
    {
        $this->parent_id = isset($data['parent_id']) && $data['parent_id'] !== '' ? (int) $data['parent_id'] : null;
        $this->collection_id = isset($data['collection_id']) && $data['collection_id'] !== '' ? (int) $data['collection_id'] : null;
        $this->page_type = (string) ($data['page_type'] ?? 'generic');
        $this->status = (string) ($data['status'] ?? 'draft');
        $this->published_at = $data['published_at'] ?? null;
        $this->scheduled_at = $data['scheduled_at'] ?? null;
        $this->sort_order = (int) ($data['sort_order'] ?? 0);
        $this->sitemap_priority = isset($data['sitemap_priority']) && $data['sitemap_priority'] !== '' ? (float) $data['sitemap_priority'] : null;
        $this->sitemap_changefreq = $data['sitemap_changefreq'] ?? null;
        $this->is_in_sitemap = (bool) ($data['is_in_sitemap'] ?? false);
        $this->translations = $data['translations'] ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
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
            'translations' => $this->translations,
        ];
    }
}
