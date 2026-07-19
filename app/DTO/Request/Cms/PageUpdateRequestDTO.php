<?php

declare(strict_types=1);

namespace App\DTO\Request\Cms;

use App\Libraries\Cms\CmsEnums;
use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'PageUpdateRequest')]
readonly class PageUpdateRequestDTO extends BaseRequestDTO
{
    #[OA\Property(description: 'parent_id', type: 'integer', nullable: true)]
    public ?int $parent_id;
    #[OA\Property(description: 'collection_id', type: 'integer', nullable: true)]
    public ?int $collection_id;
    #[OA\Property(description: 'page_type', type: 'string', nullable: true, enum: ['home', 'generic', 'contact', 'privacy', 'terms', '404', '500', 'maintenance', 'collection_index'])]
    public ?string $page_type;
    #[OA\Property(description: 'status', type: 'string', nullable: true, enum: ['draft', 'published', 'archived'])]
    public ?string $status;
    #[OA\Property(description: 'published_at', type: 'string', format: 'date-time', nullable: true)]
    public ?string $published_at;
    #[OA\Property(description: 'scheduled_at', type: 'string', format: 'date-time', nullable: true)]
    public ?string $scheduled_at;
    #[OA\Property(description: 'sort_order', type: 'integer', nullable: true)]
    public ?int $sort_order;
    #[OA\Property(description: 'sitemap_priority', type: 'number', format: 'float', nullable: true)]
    public ?float $sitemap_priority;
    #[OA\Property(description: 'sitemap_changefreq', type: 'string', nullable: true, enum: ['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'])]
    public ?string $sitemap_changefreq;
    #[OA\Property(description: 'is_in_sitemap', type: 'boolean', nullable: true)]
    public ?bool $is_in_sitemap;

    /**
     * @var array<array{language_id: int, slug: string, title: string, excerpt?: string, meta_title?: string, meta_description?: string, og_image?: array{source_kind?: string, file_id?: int|null, url?: string|null}, og_image_file_id?: int|null, og_image_url?: string, og_type?: string, canonical_url?: string, robots?: string, schema_data?: array<mixed>}>|null
     */
    #[OA\Property(description: 'translations', type: 'array', items: new OA\Items(type: 'object'), nullable: true)]
    public ?array $translations;

    /** @var array<string, mixed> */
    private array $mappedFields;

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
        $mappedFields = [];

        if (array_key_exists('parent_id', $data)) {
            $this->parent_id = $data['parent_id'] !== null && $data['parent_id'] !== '' ? (int) $data['parent_id'] : null;
            $mappedFields['parent_id'] = $this->parent_id;
        } else {
            $this->parent_id = null;
        }

        if (array_key_exists('collection_id', $data)) {
            $this->collection_id = $data['collection_id'] !== null && $data['collection_id'] !== '' ? (int) $data['collection_id'] : null;
            $mappedFields['collection_id'] = $this->collection_id;
        } else {
            $this->collection_id = null;
        }

        if (array_key_exists('page_type', $data)) {
            $this->page_type = (string) $data['page_type'];
            $mappedFields['page_type'] = $this->page_type;
        } else {
            $this->page_type = null;
        }

        if (array_key_exists('status', $data)) {
            $this->status = (string) $data['status'];
            $mappedFields['status'] = $this->status;
        } else {
            $this->status = null;
        }

        if (array_key_exists('published_at', $data)) {
            $this->published_at = $data['published_at'];
            $mappedFields['published_at'] = $this->published_at;
        } else {
            $this->published_at = null;
        }

        if (array_key_exists('scheduled_at', $data)) {
            $this->scheduled_at = $data['scheduled_at'];
            $mappedFields['scheduled_at'] = $this->scheduled_at;
        } else {
            $this->scheduled_at = null;
        }

        if (array_key_exists('sort_order', $data)) {
            $this->sort_order = (int) $data['sort_order'];
            $mappedFields['sort_order'] = $this->sort_order;
        } else {
            $this->sort_order = null;
        }

        if (array_key_exists('sitemap_priority', $data)) {
            $this->sitemap_priority = $data['sitemap_priority'] !== null && $data['sitemap_priority'] !== '' ? (float) $data['sitemap_priority'] : null;
            $mappedFields['sitemap_priority'] = $this->sitemap_priority;
        } else {
            $this->sitemap_priority = null;
        }

        if (array_key_exists('sitemap_changefreq', $data)) {
            $this->sitemap_changefreq = $data['sitemap_changefreq'];
            $mappedFields['sitemap_changefreq'] = $this->sitemap_changefreq;
        } else {
            $this->sitemap_changefreq = null;
        }

        if (array_key_exists('is_in_sitemap', $data)) {
            $this->is_in_sitemap = (bool) $data['is_in_sitemap'];
            $mappedFields['is_in_sitemap'] = $this->is_in_sitemap;
        } else {
            $this->is_in_sitemap = null;
        }

        if (array_key_exists('translations', $data)) {
            $this->translations = (array) $data['translations'];
            $mappedFields['translations'] = $this->translations;
        } else {
            $this->translations = null;
        }

        $this->mappedFields = $mappedFields;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->mappedFields;
    }
}
