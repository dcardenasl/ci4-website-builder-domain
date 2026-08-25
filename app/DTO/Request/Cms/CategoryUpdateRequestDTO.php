<?php

declare(strict_types=1);

namespace App\DTO\Request\Cms;

use App\DTO\Request\Support\TracksProvidedFields;
use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'CategoryUpdateRequest')]
readonly class CategoryUpdateRequestDTO extends BaseRequestDTO
{
    use TracksProvidedFields;

    #[OA\Property(description: 'collection_id', type: 'integer', nullable: true)]
    public ?int $collection_id;
    #[OA\Property(description: 'parent_id', type: 'integer', nullable: true)]
    public ?int $parent_id;
    #[OA\Property(description: 'sort_order', type: 'integer', nullable: true)]
    public ?int $sort_order;
    #[OA\Property(description: 'is_active', type: 'boolean', nullable: true)]
    public ?bool $is_active;

    /** @var list<array<string, mixed>>|null */
    #[OA\Property(description: 'translations', type: 'array', items: new OA\Items(type: 'object'))]
    public ?array $translations;

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'collection_id' => 'permit_empty|integer',
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
        $this->trackProvidedFields($data);
        $this->collection_id = isset($data['collection_id']) ? (int) $data['collection_id'] : null;
        $this->parent_id = isset($data['parent_id']) ? (int) $data['parent_id'] : null;
        $this->sort_order = isset($data['sort_order']) ? (int) $data['sort_order'] : null;
        $this->is_active = isset($data['is_active']) ? (bool) $data['is_active'] : null;
        $this->translations = $data['translations'] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = $this->filterProvidedFields([
            'collection_id' => $this->collection_id,
            'parent_id' => $this->parent_id,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
        ]);

        if ($this->translations !== null || $this->fieldWasProvided('translations')) {
            $result['translations'] = $this->translations;
        }

        return $result;
    }
}
