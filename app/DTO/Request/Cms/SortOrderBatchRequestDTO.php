<?php

declare(strict_types=1);

namespace App\DTO\Request\Cms;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use dcardenasl\Ci4ApiCore\Exceptions\ValidationException;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'CmsSortOrderBatchRequest')]
readonly class SortOrderBatchRequestDTO extends BaseRequestDTO
{
    /** @var list<array{id: int, sort_order: int}> */
    #[OA\Property(
        description: 'Rows to reorder atomically. IDs must belong to the requested scope.',
        type: 'array',
        minItems: 1,
        maxItems: 500,
        items: new OA\Items(type: 'object')
    )]
    public array $items;

    /** @var array<string, int|string|null> */
    #[OA\Property(
        description: 'Optional resource scope. Entries require collection_id; menu_items require menu_id; block_instances require owner_type and owner_id.',
        type: 'object',
        additionalProperties: true,
    )]
    public array $scope;

    #[OA\Property(description: 'Resource to reorder.', type: 'string', enum: ['collections', 'pages', 'entries', 'categories', 'languages', 'menu_items', 'block_instances'])]
    public string $resource;

    public function __construct(array $data, ?\CodeIgniter\Validation\ValidationInterface $validation = null)
    {
        parent::__construct($data, $validation);

        $rawItems = $data['items'] ?? null;
        if (! is_array($rawItems) || $rawItems === [] || count($rawItems) > 500) {
            throw new ValidationException(lang('Api.invalidRequest'));
        }

        $items = [];
        $seen = [];
        foreach ($rawItems as $item) {
            if (! is_array($item)) {
                throw new ValidationException(lang('Api.invalidRequest'));
            }

            $id = filter_var($item['id'] ?? null, FILTER_VALIDATE_INT);
            $sortOrder = filter_var($item['sort_order'] ?? null, FILTER_VALIDATE_INT);
            if ($id === false || $id < 1 || $sortOrder === false || $sortOrder < 0) {
                throw new ValidationException(lang('Api.invalidRequest'));
            }
            if (isset($seen[$id])) {
                throw new ValidationException(lang('Api.invalidRequest'));
            }

            $seen[$id] = true;
            $items[] = ['id' => $id, 'sort_order' => $sortOrder];
        }

        $scope = is_array($data['scope'] ?? null) ? $data['scope'] : [];
        $normalizedScope = [];
        foreach (['collection_id', 'menu_id', 'owner_id', 'parent_instance_id'] as $key) {
            if (! array_key_exists($key, $scope)) {
                continue;
            }
            if ($scope[$key] === null && $key === 'parent_instance_id') {
                $normalizedScope[$key] = null;
                continue;
            }
            $value = filter_var($scope[$key], FILTER_VALIDATE_INT);
            if ($value === false || $value < 1) {
                throw new ValidationException(lang('Api.invalidRequest'));
            }
            $normalizedScope[$key] = $value;
        }
        if (array_key_exists('owner_type', $scope)) {
            $ownerType = is_scalar($scope['owner_type']) ? (string) $scope['owner_type'] : '';
            if (! in_array($ownerType, ['page', 'entry'], true)) {
                throw new ValidationException(lang('Api.invalidRequest'));
            }
            $normalizedScope['owner_type'] = $ownerType;
        }

        $this->resource = (string) ($data['resource'] ?? '');
        $this->items = $items;
        $this->scope = $normalizedScope;
    }

    /** @return array<string, string> */
    public function rules(): array
    {
        return [
            'resource' => 'required|in_list[collections,pages,entries,categories,languages,menu_items,block_instances]',
            'items' => 'required|is_array',
            'scope' => 'permit_empty|is_array',
        ];
    }

    /** @param array<string, mixed> $data */
    protected function map(array $data): void
    {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['resource' => $this->resource, 'items' => $this->items, 'scope' => $this->scope];
    }
}
