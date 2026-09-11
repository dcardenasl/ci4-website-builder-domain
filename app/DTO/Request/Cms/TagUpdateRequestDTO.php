<?php

declare(strict_types=1);

namespace App\DTO\Request\Cms;

use App\DTO\Request\Support\TracksProvidedFields;
use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'TagUpdateRequest')]
readonly class TagUpdateRequestDTO extends BaseRequestDTO
{
    use TracksProvidedFields;

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
        $this->is_active = isset($data['is_active']) ? (bool) $data['is_active'] : null;
        $this->translations = $data['translations'] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = $this->filterProvidedFields([
            'is_active' => $this->is_active,
        ]);

        if ($this->translations !== null || $this->fieldWasProvided('translations')) {
            $result['translations'] = $this->translations;
        }

        return $result;
    }
}
