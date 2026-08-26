<?php

declare(strict_types=1);

namespace App\DTO\Response\Cms;

use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'MenuResponse',
    title: 'Menu Response',
    required: ["id","menu_key","location","is_active"]
)]
final readonly class MenuResponseDTO implements DataTransferObjectInterface
{
    /**
     * @param array<mixed>|null $translations
     */
    public function __construct(
        #[OA\Property(description: 'Unique identifier', example: 1)]
        public int $id,
        #[OA\Property(description: 'menu_key', type: 'string')]
        public string $menu_key,
        #[OA\Property(description: 'location', type: 'string')]
        public string $location,
        #[OA\Property(description: 'is_active', type: 'boolean')]
        public bool $is_active,
        #[OA\Property(property: 'created_at', description: 'Creation timestamp', example: '2026-02-26 12:00:00', nullable: true)]
        public ?string $createdAt = null,
        #[OA\Property(property: 'updated_at', description: 'Last update timestamp', example: '2026-02-26 12:00:00', nullable: true)]
        public ?string $updatedAt = null,
        #[OA\Property(property: 'translations', type: 'array', items: new OA\Items(type: 'object'), nullable: true)]
        public ?array $translations = null,
        #[OA\Property(property: 'items_count', description: 'Number of menu items', type: 'integer')]
        public int $items_count = 0
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new static(
            id: (int) ($data['id'] ?? 0),
            menu_key: (string) ($data['menu_key'] ?? ''),
            location: (string) ($data['location'] ?? ''),
            is_active: (bool) ($data['is_active'] ?? false),
            createdAt: DateValue::toString($data['created_at'] ?? null),
            updatedAt: DateValue::toString($data['updated_at'] ?? null),
            translations: $data['translations'] ?? null,
            items_count: (int) ($data['items_count'] ?? 0),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $res = [
            'id'         => $this->id,
            'menu_key'   => $this->menu_key,
            'location'   => $this->location,
            'is_active'  => $this->is_active,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];

        if ($this->translations !== null) {
            $res['translations'] = $this->translations;
        }
        $res['items_count'] = $this->items_count;

        return $res;
    }
}
