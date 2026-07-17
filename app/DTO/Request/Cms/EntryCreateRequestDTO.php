<?php

declare(strict_types=1);

namespace App\DTO\Request\Cms;

use App\Libraries\Cms\CmsEnums;
use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'EntryCreateRequest')]
readonly class EntryCreateRequestDTO extends BaseRequestDTO
{
    #[OA\Property(description: 'collection_id', type: 'integer')]
    public int $collection_id;
    #[OA\Property(description: 'author_id', type: 'integer', nullable: true)]
    public ?int $author_id;
    #[OA\Property(description: 'workflow_status', type: 'string', enum: ['draft', 'in_review', 'approved', 'published', 'archived'])]
    public string $workflow_status;
    #[OA\Property(description: 'published_at', type: 'string', format: 'date-time', nullable: true)]
    public ?string $published_at;
    #[OA\Property(description: 'scheduled_at', type: 'string', format: 'date-time', nullable: true)]
    public ?string $scheduled_at;
    #[OA\Property(description: 'is_featured', type: 'boolean')]
    public bool $is_featured;
    #[OA\Property(description: 'view_count', type: 'integer')]
    public int $view_count;
    #[OA\Property(description: 'sort_order', type: 'integer')]
    public int $sort_order;
    #[OA\Property(description: 'sitemap_priority', type: 'number', format: 'float', nullable: true)]
    public ?float $sitemap_priority;
    #[OA\Property(description: 'sitemap_changefreq', type: 'string', nullable: true, enum: ['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'])]
    public ?string $sitemap_changefreq;
    #[OA\Property(description: 'is_in_sitemap', type: 'boolean')]
    public bool $is_in_sitemap;

    /**
     * @var array<array{language_id: int, slug: string, title: string, excerpt?: string, featured_image?: array{source_kind?: string, file_id?: int|null, url?: string|null}, meta_title?: string, meta_description?: string, og_image?: array{source_kind?: string, file_id?: int|null, url?: string|null}, og_type?: string, canonical_url?: string, robots?: string, schema_data?: array<mixed>}>
     */
    #[OA\Property(description: 'translations', type: 'array', items: new OA\Items(type: 'object'))]
    public array $translations;

    /**
     * @var array<string, mixed>|null
     */
    #[OA\Property(description: 'Extra fields captured by wizard (non-standard entry data)', type: 'object', nullable: true)]
    public ?array $wizard_extra;

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'collection_id' => 'required|integer',
            'author_id' => 'permit_empty|integer',
            'workflow_status' => 'required|' . CmsEnums::inListRule(CmsEnums::WORKFLOW_STATUS),
            'published_at' => 'permit_empty|valid_date',
            'scheduled_at' => 'permit_empty|valid_date',
            'is_featured' => 'permit_empty|boolean_like',
            'view_count' => 'permit_empty|integer',
            'sort_order' => 'permit_empty|integer',
            'sitemap_priority' => 'permit_empty|decimal',
            'sitemap_changefreq' => 'permit_empty|' . CmsEnums::inListRule(CmsEnums::SITEMAP_CHANGEFREQ),
            'is_in_sitemap' => 'permit_empty|boolean_like',
            'translations' => 'permit_empty',
            'wizard_extra' => 'permit_empty',
            'translations.*.language_id' => 'required_with[translations]|is_natural_no_zero',
            'translations.*.slug' => 'required_with[translations]|string|max_length[150]',
            'translations.*.title' => 'required_with[translations]|string|max_length[255]',
            'translations.*.excerpt' => 'permit_empty|string|max_length[500]',
            'translations.*.featured_image' => 'permit_empty',
            'translations.*.featured_file_id' => 'permit_empty|integer',
            'translations.*.featured_image_url' => 'permit_empty|string|max_length[2048]',
            'translations.*.meta_title' => 'permit_empty|string|max_length[255]',
            'translations.*.meta_description' => 'permit_empty|string|max_length[500]',
            'translations.*.og_image' => 'permit_empty',
            'translations.*.og_image_file_id' => 'permit_empty|integer',
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
        $this->collection_id = (int) ($data['collection_id'] ?? 0);
        $this->author_id = isset($data['author_id']) && $data['author_id'] !== '' ? (int) $data['author_id'] : null;
        $this->workflow_status = (string) ($data['workflow_status'] ?? '');
        $this->published_at = $data['published_at'] ?? null;
        $this->scheduled_at = $data['scheduled_at'] ?? null;
        $this->is_featured = (bool) ($data['is_featured'] ?? false);
        $this->view_count = (int) ($data['view_count'] ?? 0);
        $this->sort_order = (int) ($data['sort_order'] ?? 0);
        $this->sitemap_priority = isset($data['sitemap_priority']) && $data['sitemap_priority'] !== '' ? (float) $data['sitemap_priority'] : null;
        $this->sitemap_changefreq = $data['sitemap_changefreq'] ?? null;
        $this->is_in_sitemap = (bool) ($data['is_in_sitemap'] ?? false);
        $this->translations = $data['translations'] ?? [];
        $wizardExtra = $data['wizard_extra'] ?? null;
        $this->wizard_extra = is_array($wizardExtra) ? $wizardExtra : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'collection_id' => $this->collection_id,
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
            'translations' => $this->translations,
            'wizard_extra' => $this->wizard_extra !== null
                ? json_encode($this->wizard_extra, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                : null,
        ];
    }
}
