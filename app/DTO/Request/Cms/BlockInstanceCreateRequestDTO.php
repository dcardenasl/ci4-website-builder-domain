<?php

declare(strict_types=1);

namespace App\DTO\Request\Cms;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'BlockInstanceCreateRequest')]
readonly class BlockInstanceCreateRequestDTO extends BaseRequestDTO
{
    #[OA\Property(description: 'block_id', type: 'integer')]
    public int $block_id;
    #[OA\Property(description: 'owner_type', type: 'string')]
    public string $owner_type;
    #[OA\Property(description: 'owner_id', type: 'integer')]
    public int $owner_id;
    #[OA\Property(description: 'parent_instance_id', type: 'integer', nullable: true)]
    public ?int $parent_instance_id;
    #[OA\Property(description: 'sort_order', type: 'integer')]
    public int $sort_order;
    #[OA\Property(description: 'column_index', type: 'integer', nullable: true)]
    public ?int $column_index;
    #[OA\Property(description: 'is_active', type: 'boolean')]
    public bool $is_active;
    /** @var array<string, mixed>|null */
    #[OA\Property(description: 'block_config', type: 'object', nullable: true)]
    public ?array $block_config;

    /**
     * @var array<array{language_id: int, block_data: array<string, mixed>, is_published: bool}>
     */
    #[OA\Property(description: 'translations', type: 'array', items: new OA\Items(type: 'object'))]
    public array $translations;

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'block_id' => 'required|is_natural_no_zero|is_not_unique[cms_content_blocks.id]',
            'owner_type' => 'required|string|in_list[page,entry]',
            'owner_id' => 'required|integer',
            'parent_instance_id' => 'permit_empty|is_natural_no_zero|is_not_unique[cms_block_instances.id]',
            'sort_order' => 'required|integer',
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
        $this->block_id = (int) ($data['block_id'] ?? 0);
        $this->owner_type = (string) ($data['owner_type'] ?? '');
        $this->owner_id = (int) ($data['owner_id'] ?? 0);
        $this->parent_instance_id = isset($data['parent_instance_id']) ? (int) $data['parent_instance_id'] : null;
        $this->sort_order = (int) ($data['sort_order'] ?? 0);
        $this->column_index = isset($data['column_index']) ? (int) $data['column_index'] : null;
        $this->is_active = (bool) ($data['is_active'] ?? false);
        $blockConfig = $data['block_config'] ?? null;
        if (is_string($blockConfig) && trim($blockConfig) !== '') {
            $decoded = json_decode($blockConfig, true);
            $blockConfig = json_last_error() === JSON_ERROR_NONE ? $decoded : null;
        }
        $this->block_config = is_array($blockConfig) ? $blockConfig : null;
        $this->translations = $data['translations'] ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'block_id' => $this->block_id,
            'owner_type' => $this->owner_type,
            'owner_id' => $this->owner_id,
            'parent_instance_id' => $this->parent_instance_id,
            'sort_order' => $this->sort_order,
            'column_index' => $this->column_index,
            'is_active' => $this->is_active,
            'block_config' => $this->block_config,
            'translations' => $this->translations,
        ];
    }
}
