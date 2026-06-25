<?php

declare(strict_types=1);

namespace App\DTO\Request\Cms;

use App\Libraries\Cms\BlockTemplateNormalizer;
use App\Libraries\Cms\CmsEnums;
use App\Validators\BlockTemplateValidator;
use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'CollectionUpdateRequest')]
readonly class CollectionUpdateRequestDTO extends BaseRequestDTO
{
    #[OA\Property(description: 'collection_key', type: 'string', nullable: true)]
    public ?string $collection_key;
    #[OA\Property(description: 'is_active', type: 'boolean', nullable: true)]
    public ?bool $is_active;
    #[OA\Property(description: 'requires_approval', type: 'boolean', nullable: true)]
    public ?bool $requires_approval;
    #[OA\Property(description: 'enables_categories', type: 'boolean', nullable: true)]
    public ?bool $enables_categories;
    #[OA\Property(description: 'enables_tags', type: 'boolean', nullable: true)]
    public ?bool $enables_tags;
    #[OA\Property(description: 'default_sitemap_priority', type: 'number', format: 'float', nullable: true)]
    public ?float $default_sitemap_priority;
    #[OA\Property(description: 'default_changefreq', type: 'string', nullable: true, enum: ['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'])]
    public ?string $default_changefreq;
    #[OA\Property(description: 'sort_order', type: 'integer', nullable: true)]
    public ?int $sort_order;

    /**
     * @var array<string, mixed>|null
     */
    #[OA\Property(description: 'block_template', type: 'object', nullable: true)]
    public ?array $block_template;

    /**
     * @var array<array{language_id: int, slug?: string, name: string, description?: string, listing_title?: string, listing_intro?: string, default_meta_title?: string, default_meta_description?: string}>|null
     */
    #[OA\Property(description: 'translations', type: 'array', items: new OA\Items(type: 'object'))]
    public ?array $translations;

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'collection_key' => 'permit_empty|string|max_length[50]',
            'is_active' => 'permit_empty|boolean_like',
            'requires_approval' => 'permit_empty|boolean_like',
            'enables_categories' => 'permit_empty|boolean_like',
            'enables_tags' => 'permit_empty|boolean_like',
            'default_sitemap_priority' => 'permit_empty|decimal',
            'default_changefreq' => 'permit_empty|' . CmsEnums::inListRule(CmsEnums::SITEMAP_CHANGEFREQ),
            'sort_order' => 'permit_empty|integer',
            'translations' => 'permit_empty',
            'translations.*.language_id' => 'required_with[translations]|is_natural_no_zero',
            'translations.*.slug' => 'required_with[translations]|string|min_length[1]|max_length[150]',
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
        $this->collection_key = $data['collection_key'] ?? null;
        $this->is_active = isset($data['is_active']) ? (bool) $data['is_active'] : null;
        $this->requires_approval = isset($data['requires_approval']) ? (bool) $data['requires_approval'] : null;
        $this->enables_categories = isset($data['enables_categories']) ? (bool) $data['enables_categories'] : null;
        $this->enables_tags = isset($data['enables_tags']) ? (bool) $data['enables_tags'] : null;
        $this->default_sitemap_priority = isset($data['default_sitemap_priority']) ? (float) $data['default_sitemap_priority'] : null;
        $this->default_changefreq = $data['default_changefreq'] ?? null;
        $this->sort_order = isset($data['sort_order']) ? (int) $data['sort_order'] : null;
        $this->block_template = array_key_exists('block_template', $data)
            ? $this->parseBlockTemplate($data['block_template'])
            : null;
        $this->translations = $data['translations'] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = array_filter([
            'collection_key' => $this->collection_key,
            'is_active' => $this->is_active,
            'requires_approval' => $this->requires_approval,
            'enables_categories' => $this->enables_categories,
            'enables_tags' => $this->enables_tags,
            'default_sitemap_priority' => $this->default_sitemap_priority,
            'default_changefreq' => $this->default_changefreq,
            'sort_order' => $this->sort_order,
            'translations' => $this->translations,
        ], static fn (mixed $value): bool => $value !== null);

        if ($this->block_template !== null) {
            $data['block_template'] = json_encode($this->block_template, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return $data;
    }

    /**
     * @param mixed $raw
     * @return array<string, mixed>|null
     */
    private function parseBlockTemplate(mixed $raw): ?array
    {
        $normalized = BlockTemplateNormalizer::normalize($raw);
        if ($normalized === null) {
            return null;
        }

        (new BlockTemplateValidator())->validate($normalized);

        return $normalized;
    }
}
