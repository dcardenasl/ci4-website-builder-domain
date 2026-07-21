<?php

declare(strict_types=1);

namespace App\DTO\Response\Cms;

use App\Libraries\Cms\JsonCastNormalizer;
use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'BlockTypeResponse',
    title: 'BlockType Response',
    required: ["id","block_key","name","category","schema_definition","supports_pages","supports_entries","is_container","is_active","sort_order"]
)]
final readonly class BlockTypeResponseDTO implements DataTransferObjectInterface
{
    /**
     * @param array<string, mixed> $schema_definition
     * @param array<string, mixed> $fields
     * @param array<string, mixed> $config_fields
     */
    public function __construct(
        #[OA\Property(description: 'Unique identifier', example: 1)]
        public int $id,
        #[OA\Property(description: 'block_key', type: 'string')]
        public string $block_key,
        #[OA\Property(description: 'name', type: 'string')]
        public string $name,
        #[OA\Property(description: 'description', type: 'string', nullable: true)]
        public ?string $description,
        #[OA\Property(description: 'category', type: 'string')]
        public string $category,
        #[OA\Property(description: 'icon', type: 'string', nullable: true)]
        public ?string $icon,
        #[OA\Property(description: 'schema_definition', type: 'object')]
        public array $schema_definition,
        #[OA\Property(description: 'Content fields extracted from schema_definition.fields', type: 'object')]
        public array $fields,
        #[OA\Property(description: 'Config fields extracted from schema_definition.config_fields', type: 'object')]
        public array $config_fields,
        #[OA\Property(description: 'supports_pages', type: 'boolean')]
        public bool $supports_pages,
        #[OA\Property(description: 'supports_entries', type: 'boolean')]
        public bool $supports_entries,
        #[OA\Property(description: 'is_container', type: 'boolean')]
        public bool $is_container,
        #[OA\Property(description: 'is_active', type: 'boolean')]
        public bool $is_active,
        #[OA\Property(description: 'sort_order', type: 'integer')]
        public int $sort_order,
        #[OA\Property(property: 'created_at', description: 'Creation timestamp', example: '2026-02-26 12:00:00', nullable: true)]
        public ?string $createdAt = null,
        #[OA\Property(property: 'updated_at', description: 'Last update timestamp', example: '2026-02-26 12:00:00', nullable: true)]
        public ?string $updatedAt = null
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        $schema = JsonCastNormalizer::toArray($data['schema_definition'] ?? []);

        return new static(
            id: (int) ($data['id'] ?? 0),
            block_key: (string) ($data['block_key'] ?? ''),
            name: (string) ($data['name'] ?? ''),
            description: $data['description'] ?? null,
            category: (string) ($data['category'] ?? ''),
            icon: $data['icon'] ?? null,
            schema_definition: $schema,
            fields: (array) ($schema['fields'] ?? []),
            config_fields: (array) ($schema['config_fields'] ?? []),
            supports_pages: (bool) ($data['supports_pages'] ?? false),
            supports_entries: (bool) ($data['supports_entries'] ?? false),
            is_container: (bool) ($data['is_container'] ?? false),
            is_active: (bool) ($data['is_active'] ?? false),
            sort_order: (int) ($data['sort_order'] ?? 0),
            createdAt: DateValue::toString($data['created_at'] ?? null),
            updatedAt: DateValue::toString($data['updated_at'] ?? null),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'block_key' => $this->block_key,
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category,
            'icon' => $this->icon,
            'schema_definition' => $this->schema_definition,
            'fields' => $this->fields,
            'config_fields' => $this->config_fields,
            'supports_pages' => $this->supports_pages,
            'supports_entries' => $this->supports_entries,
            'is_container' => $this->is_container,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
