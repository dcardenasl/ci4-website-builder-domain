<?php

declare(strict_types=1);

namespace App\DTO\Response\Cms;

use App\Libraries\Cms\BlockTemplateNormalizer;
use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CollectionResponse',
    title: 'Collection Response',
    required: ["id","collection_key","collection_type","is_active","requires_approval","enables_categories","enables_tags","sort_order"]
)]
final readonly class CollectionResponseDTO implements DataTransferObjectInterface
{
    /**
     * @param array<string, mixed>|null $block_template
     * @param array<string, mixed>|null $wizard_config
     * @param array<int, array<string, mixed>> $translations
     */
    public function __construct(
        #[OA\Property(description: 'Unique identifier', example: 1)]
        public int $id,
        #[OA\Property(description: 'collection_key', type: 'string')]
        public string $collection_key,
        #[OA\Property(description: 'collection_type', type: 'string')]
        public string $collection_type,
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
        #[OA\Property(description: 'Block template defining fixed structure inherited by entries', type: 'object', nullable: true)]
        public ?array $block_template = null,
        #[OA\Property(description: 'Wizard step configuration for non-technical content creation', type: 'object', nullable: true)]
        public ?array $wizard_config = null,
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
        $blockTemplate = $data['block_template'] ?? null;
        try {
            $blockTemplate = BlockTemplateNormalizer::normalize($blockTemplate);
        } catch (\Throwable) {
            if (is_string($blockTemplate)) {
                $decoded = json_decode($blockTemplate, true);
                $blockTemplate = is_array($decoded) ? $decoded : null;
            } elseif (is_object($blockTemplate)) {
                $encoded = json_encode($blockTemplate);
                $blockTemplate = $encoded === false ? null : json_decode($encoded, true);
            }
        }

        $wizardConfig = $data['wizard_config'] ?? null;
        if (is_object($wizardConfig)) {
            $encoded = json_encode($wizardConfig);
            $wizardConfig = $encoded === false ? null : json_decode($encoded, true);
        } elseif (is_string($wizardConfig)) {
            $decoded = json_decode($wizardConfig, true);
            $wizardConfig = is_array($decoded) ? $decoded : null;
        }

        return new static(
            id: (int) ($data['id'] ?? 0),
            collection_key: (string) ($data['collection_key'] ?? ''),
            collection_type: (string) ($data['collection_type'] ?? 'other'),
            is_active: (bool) ($data['is_active'] ?? false),
            requires_approval: (bool) ($data['requires_approval'] ?? false),
            enables_categories: (bool) ($data['enables_categories'] ?? false),
            enables_tags: (bool) ($data['enables_tags'] ?? false),
            default_sitemap_priority: isset($data['default_sitemap_priority']) ? (float) $data['default_sitemap_priority'] : null,
            default_changefreq: $data['default_changefreq'] ?? null,
            sort_order: (int) ($data['sort_order'] ?? 0),
            block_template: is_array($blockTemplate) ? $blockTemplate : null,
            wizard_config: is_array($wizardConfig) ? $wizardConfig : null,
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
            'collection_type' => $this->collection_type,
            'is_active' => $this->is_active,
            'requires_approval' => $this->requires_approval,
            'enables_categories' => $this->enables_categories,
            'enables_tags' => $this->enables_tags,
            'default_sitemap_priority' => $this->default_sitemap_priority,
            'default_changefreq' => $this->default_changefreq,
            'sort_order' => $this->sort_order,
            'block_template' => $this->block_template,
            'wizard_config'  => $this->wizard_config,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'translations' => $this->translations,
        ];
    }
}
