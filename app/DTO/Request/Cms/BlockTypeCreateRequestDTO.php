<?php

declare(strict_types=1);

namespace App\DTO\Request\Cms;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'BlockTypeCreateRequest')]
readonly class BlockTypeCreateRequestDTO extends BaseRequestDTO
{
    #[OA\Property(description: 'block_key', type: 'string')]
    public string $block_key;
    #[OA\Property(description: 'name', type: 'string')]
    public string $name;
    #[OA\Property(description: 'description', type: 'string', nullable: true)]
    public ?string $description;
    #[OA\Property(description: 'category', type: 'string')]
    public string $category;
    #[OA\Property(description: 'icon', type: 'string', nullable: true)]
    public ?string $icon;
    /** @var array<string, mixed> */
    #[OA\Property(description: 'schema_definition', type: 'object')]
    public array $schema_definition;
    #[OA\Property(description: 'supports_pages', type: 'boolean')]
    public bool $supports_pages;
    #[OA\Property(description: 'supports_entries', type: 'boolean')]
    public bool $supports_entries;
    #[OA\Property(description: 'is_container', type: 'boolean')]
    public bool $is_container;
    #[OA\Property(description: 'is_active', type: 'boolean')]
    public bool $is_active;
    #[OA\Property(description: 'sort_order', type: 'integer')]
    public int $sort_order;

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'block_key' => 'required|string|max_length[255]|is_unique[cms_content_blocks.block_key]',
            'name' => 'required|string|max_length[255]',
            'description' => 'permit_empty|string',
            'category' => 'required|string|max_length[255]',
            'icon' => 'permit_empty|string|max_length[255]',
            'schema_definition' => 'required|permit_empty',
            'supports_pages' => 'permit_empty|boolean_like',
            'supports_entries' => 'permit_empty|boolean_like',
            'is_container' => 'permit_empty|boolean_like',
            'is_active' => 'permit_empty|boolean_like',
            'sort_order' => 'required|integer',
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function map(array $data): void
    {
        $this->block_key = (string) ($data['block_key'] ?? '');
        $this->name = (string) ($data['name'] ?? '');
        $this->description = $data['description'] ?? null;
        $this->category = (string) ($data['category'] ?? '');
        $this->icon = $data['icon'] ?? null;
        $this->schema_definition = (array) ($data['schema_definition'] ?? []);
        $this->supports_pages = (bool) ($data['supports_pages'] ?? false);
        $this->supports_entries = (bool) ($data['supports_entries'] ?? false);
        $this->is_container = (bool) ($data['is_container'] ?? false);
        $this->is_active = (bool) ($data['is_active'] ?? false);
        $this->sort_order = (int) ($data['sort_order'] ?? 0);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
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
        ];
    }
}
