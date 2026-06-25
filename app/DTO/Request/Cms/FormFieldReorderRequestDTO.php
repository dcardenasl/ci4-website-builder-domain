<?php

declare(strict_types=1);

namespace App\DTO\Request\Cms;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;

readonly class FormFieldReorderRequestDTO extends BaseRequestDTO
{
    /** @var list<int> */
    public array $ordered_ids;

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'ordered_ids' => 'required',
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function map(array $data): void
    {
        $raw = is_array($data['ordered_ids'] ?? null) ? $data['ordered_ids'] : [];
        $this->ordered_ids = array_values(array_map('intval', $raw));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return ['ordered_ids' => $this->ordered_ids];
    }
}
