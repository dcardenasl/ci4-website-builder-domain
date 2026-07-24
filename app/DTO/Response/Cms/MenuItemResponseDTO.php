<?php

declare(strict_types=1);

namespace App\DTO\Response\Cms;

use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'MenuItemResponse',
    title: 'MenuItem Response',
    required: ["id","menu_id","link_type","link_target","sort_order","is_active"]
)]
final readonly class MenuItemResponseDTO implements DataTransferObjectInterface
{
    /**
     * @param array<mixed>|null $translations
     */
    public function __construct(
        #[OA\Property(description: 'Unique identifier', example: 1)]
        public int $id,
        #[OA\Property(description: 'menu_id', type: 'integer')]
        public int $menu_id,
        #[OA\Property(description: 'parent_id', type: 'integer', nullable: true)]
        public ?int $parent_id,
        #[OA\Property(description: 'link_type', type: 'string')]
        public string $link_type,
        #[OA\Property(description: 'page_id', type: 'integer', nullable: true)]
        public ?int $page_id,
        #[OA\Property(description: 'entry_id', type: 'integer', nullable: true)]
        public ?int $entry_id,
        #[OA\Property(description: 'collection_id', type: 'integer', nullable: true)]
        public ?int $collection_id,
        #[OA\Property(description: 'link_target', type: 'string')]
        public string $link_target,
        #[OA\Property(description: 'icon', type: 'string', nullable: true)]
        public ?string $icon,
        #[OA\Property(description: 'css_class', type: 'string', nullable: true)]
        public ?string $css_class,
        #[OA\Property(description: 'sort_order', type: 'integer')]
        public int $sort_order,
        #[OA\Property(description: 'is_active', type: 'boolean')]
        public bool $is_active,
        #[OA\Property(property: 'created_at', description: 'Creation timestamp', example: '2026-02-26 12:00:00', nullable: true)]
        public ?string $createdAt = null,
        #[OA\Property(property: 'updated_at', description: 'Last update timestamp', example: '2026-02-26 12:00:00', nullable: true)]
        public ?string $updatedAt = null,
        #[OA\Property(property: 'translations', type: 'array', items: new OA\Items(type: 'object'), nullable: true)]
        public ?array $translations = null
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new static(
            id: (int) ($data['id'] ?? 0),
            menu_id: (int) ($data['menu_id'] ?? 0),
            parent_id: isset($data['parent_id']) ? (int) $data['parent_id'] : null,
            link_type: (string) ($data['link_type'] ?? ''),
            page_id: isset($data['page_id']) ? (int) $data['page_id'] : null,
            entry_id: isset($data['entry_id']) ? (int) $data['entry_id'] : null,
            collection_id: isset($data['collection_id']) ? (int) $data['collection_id'] : null,
            link_target: (string) ($data['link_target'] ?? ''),
            icon: $data['icon'] ?? null,
            css_class: $data['css_class'] ?? null,
            sort_order: (int) ($data['sort_order'] ?? 0),
            is_active: (bool) ($data['is_active'] ?? false),
            createdAt: DateValue::toString($data['created_at'] ?? null),
            updatedAt: DateValue::toString($data['updated_at'] ?? null),
            translations: $data['translations'] ?? null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $res = [
            'id'            => $this->id,
            'menu_id'       => $this->menu_id,
            'parent_id'     => $this->parent_id,
            'link_type'     => $this->link_type,
            'page_id'       => $this->page_id,
            'entry_id'      => $this->entry_id,
            'collection_id' => $this->collection_id,
            'link_target'   => $this->link_target,
            'icon'          => $this->icon,
            'css_class'     => $this->css_class,
            'sort_order'    => $this->sort_order,
            'is_active'     => $this->is_active,
            'created_at'    => $this->createdAt,
            'updated_at'    => $this->updatedAt,
        ];

        if ($this->translations !== null) {
            $res['translations'] = $this->translations;
        }

        return $res;
    }
}
