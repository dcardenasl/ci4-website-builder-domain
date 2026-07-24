<?php

declare(strict_types=1);

namespace App\DTO\Request\Cms;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'CategoryCreateRequest')]
readonly class CategoryCreateRequestDTO extends BaseRequestDTO
{
    #[OA\Property(description: 'collection_id', type: 'integer')]
    public int $collection_id;
    #[OA\Property(description: 'parent_id', type: 'integer', nullable: true)]
    public ?int $parent_id;
    #[OA\Property(description: 'sort_order', type: 'integer')]
    public int $sort_order;
    #[OA\Property(description: 'is_active', type: 'boolean')]
    public bool $is_active;

    /** @var list<array<string, mixed>>|null */
    #[OA\Property(description: 'translations', type: 'array', items: new OA\Items(type: 'object'))]
    public ?array $translations;

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'collection_id' => 'required|integer',
            'parent_id' => 'permit_empty|integer',
            'sort_order' => 'permit_empty|integer',
            'is_active' => 'permit_empty|boolean_like',
            'translations' => 'permit_empty',
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function map(array $data): void
    {
        $this->collection_id = (int) ($data['collection_id'] ?? 0);
        $this->parent_id = isset($data['parent_id']) ? (int) $data['parent_id'] : null;
        $this->sort_order = (int) ($data['sort_order'] ?? 0);
        $this->is_active = (bool) ($data['is_active'] ?? false);
        $this->translations = $data['translations'] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'collection_id' => $this->collection_id,
            'parent_id' => $this->parent_id,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'translations' => $this->translations,
        ];
    }
}
