<?php

declare(strict_types=1);

namespace App\DTO\Request\Cms;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'BlockInstanceUpdateRequest')]
readonly class BlockInstanceUpdateRequestDTO extends BaseRequestDTO
{
    #[OA\Property(description: 'block_id', type: 'integer', nullable: true)]
    public ?int $block_id;
    #[OA\Property(description: 'owner_type', type: 'string', nullable: true)]
    public ?string $owner_type;
    #[OA\Property(description: 'owner_id', type: 'integer', nullable: true)]
    public ?int $owner_id;
    #[OA\Property(description: 'parent_instance_id', type: 'integer', nullable: true)]
    public ?int $parent_instance_id;
    #[OA\Property(description: 'sort_order', type: 'integer', nullable: true)]
    public ?int $sort_order;
    #[OA\Property(description: 'column_index', type: 'integer', nullable: true)]
    public ?int $column_index;
    #[OA\Property(description: 'is_active', type: 'boolean', nullable: true)]
    public ?bool $is_active;
    #[OA\Property(description: 'block_config', type: 'object', nullable: true)]
    public ?array $block_config;

    /**
     * @var array<array{language_id: int, block_data: array<string, mixed>, is_published: bool}>
     */
    #[OA\Property(description: 'translations', type: 'array', items: new OA\Items(type: 'object'))]
    public ?array $translations;

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'block_id' => 'permit_empty|is_natural_no_zero|is_not_unique[cms_content_blocks.id]',
            'owner_type' => 'permit_empty|string|in_list[page,entry]',
            'owner_id' => 'permit_empty|integer',
            'parent_instance_id' => 'permit_empty|is_natural_no_zero|is_not_unique[cms_block_instances.id]',
            'sort_order' => 'permit_empty|integer',
            'column_index' => 'permit_empty|integer',
            'is_active' => 'permit_empty|boolean_like',
            'block_config' => 'permit_empty',
            'translations' => 'permit_empty',
            'translations.*.language_id' => 'required_with[translations]|is_natural_no_zero|is_not_unique[cms_languages.id]',
            'translations.*.block_data' => 'permit_empty|array',
            'translations.*.is_published' => 'required_with[translations]|boolean_like',
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function map(array $data): void
    {
        $this->block_id = isset($data['block_id']) ? (int) $data['block_id'] : null;
        $this->owner_type = $data['owner_type'] ?? null;
        $this->owner_id = isset($data['owner_id']) ? (int) $data['owner_id'] : null;
        $this->parent_instance_id = isset($data['parent_instance_id']) ? (int) $data['parent_instance_id'] : null;
        $this->sort_order = isset($data['sort_order']) ? (int) $data['sort_order'] : null;
        $this->column_index = isset($data['column_index']) ? (int) $data['column_index'] : null;
        $this->is_active = isset($data['is_active']) ? (bool) $data['is_active'] : null;
        $blockConfig = $data['block_config'] ?? null;
        if (is_string($blockConfig) && trim($blockConfig) !== '') {
            $decoded = json_decode($blockConfig, true);
            $blockConfig = json_last_error() === JSON_ERROR_NONE ? $decoded : null;
        }
        $this->block_config = is_array($blockConfig) ? $blockConfig : null;
        $this->translations = isset($data['translations']) ? (array) $data['translations'] : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'block_id' => $this->block_id,
            'owner_type' => $this->owner_type,
            'owner_id' => $this->owner_id,
            'parent_instance_id' => $this->parent_instance_id,
            'sort_order' => $this->sort_order,
            'column_index' => $this->column_index,
            'is_active' => $this->is_active,
            'block_config' => $this->block_config,
            'translations' => $this->translations,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
