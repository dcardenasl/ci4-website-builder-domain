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
        description: 'Rows to reorder atomically.',
        type: 'array',
        minItems: 1,
        maxItems: 500,
        items: new OA\Items(type: 'object')
    )]
    public array $items;

    #[OA\Property(description: 'Resource to reorder.', type: 'string', enum: ['collections'])]
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
            if ($id === false || $id < 1 || $sortOrder === false || $sortOrder < 0 || isset($seen[$id])) {
                throw new ValidationException(lang('Api.invalidRequest'));
            }

            $seen[$id] = true;
            $items[] = ['id' => $id, 'sort_order' => $sortOrder];
        }

        $this->resource = (string) ($data['resource'] ?? '');
        $this->items = $items;
    }

    /** @return array<string, string> */
    public function rules(): array
    {
        return [
            'resource' => 'required|in_list[collections]',
            'items' => 'required|is_array',
        ];
    }

    /** @param array<string, mixed> $data */
    protected function map(array $data): void
    {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['resource' => $this->resource, 'items' => $this->items];
    }
}
