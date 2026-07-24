<?php

declare(strict_types=1);

namespace App\DTO\Request\Cms;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'TagCreateRequest')]
readonly class TagCreateRequestDTO extends BaseRequestDTO
{
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
            'is_active' => 'permit_empty|boolean_like',
            'translations' => 'permit_empty',
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function map(array $data): void
    {
        $this->is_active = (bool) ($data['is_active'] ?? false);
        $this->translations = $data['translations'] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'is_active' => $this->is_active,
            'translations' => $this->translations,
        ];
    }
}
