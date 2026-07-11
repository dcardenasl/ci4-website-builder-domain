<?php

declare(strict_types=1);

namespace App\DTO\Request\Cms;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'BlockTypeUpdateRequest')]
readonly class BlockTypeUpdateRequestDTO extends BaseRequestDTO
{
    #[OA\Property(description: 'block_key', type: 'string', nullable: true)]
    public ?string $block_key;
    #[OA\Property(description: 'name', type: 'string', nullable: true)]
    public ?string $name;
    #[OA\Property(description: 'description', type: 'string', nullable: true)]
    public ?string $description;
    #[OA\Property(description: 'category', type: 'string', nullable: true)]
    public ?string $category;
    #[OA\Property(description: 'icon', type: 'string', nullable: true)]
    public ?string $icon;
    /** @var array<string, mixed>|null */
    #[OA\Property(description: 'schema_definition', type: 'object', nullable: true)]
    public ?array $schema_definition;
    #[OA\Property(description: 'supports_pages', type: 'boolean', nullable: true)]
    public ?bool $supports_pages;
    #[OA\Property(description: 'supports_entries', type: 'boolean', nullable: true)]
    public ?bool $supports_entries;
    #[OA\Property(description: 'is_container', type: 'boolean', nullable: true)]
    public ?bool $is_container;
    #[OA\Property(description: 'is_active', type: 'boolean', nullable: true)]
    public ?bool $is_active;
    #[OA\Property(description: 'sort_order', type: 'integer', nullable: true)]
    public ?int $sort_order;

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'block_key' => 'permit_empty|string|max_length[255]',
            'name' => 'permit_empty|string|max_length[255]',
            'description' => 'permit_empty|string',
            'category' => 'permit_empty|string|max_length[255]',
            'icon' => 'permit_empty|string|max_length[255]',
            'schema_definition' => 'permit_empty|permit_empty',
            'supports_pages' => 'permit_empty|boolean_like',
            'supports_entries' => 'permit_empty|boolean_like',
            'is_container' => 'permit_empty|boolean_like',
            'is_active' => 'permit_empty|boolean_like',
            'sort_order' => 'permit_empty|integer',
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function map(array $data): void
    {
        $this->block_key = $data['block_key'] ?? null;
        $this->name = $data['name'] ?? null;
        $this->description = $data['description'] ?? null;
        $this->category = $data['category'] ?? null;
        $this->icon = $data['icon'] ?? null;
        $this->schema_definition = isset($data['schema_definition']) ? (array) $data['schema_definition'] : null;
        $this->supports_pages = isset($data['supports_pages']) ? (bool) $data['supports_pages'] : null;
        $this->supports_entries = isset($data['supports_entries']) ? (bool) $data['supports_entries'] : null;
        $this->is_container = isset($data['is_container']) ? (bool) $data['is_container'] : null;
        $this->is_active = isset($data['is_active']) ? (bool) $data['is_active'] : null;
        $this->sort_order = isset($data['sort_order']) ? (int) $data['sort_order'] : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'block_key' => $this->block_key,
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category,
            'icon' => $this->icon,
            'schema_definition' => $this->schema_definition,
            'supports_pages' => $this->supports_pages,
            'supports_entries' => $this->supports_entries,
            'is_container' => $this->is_container,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
