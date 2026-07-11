<?php

declare(strict_types=1);

namespace App\DTO\Request\Cms;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'LanguageUpdateRequest')]
readonly class LanguageUpdateRequestDTO extends BaseRequestDTO
{
    #[OA\Property(description: 'code', type: 'string')]
    public ?string $code;
    #[OA\Property(description: 'name', type: 'string')]
    public ?string $name;
    #[OA\Property(description: 'native_name', type: 'string')]
    public ?string $nativeName;
    #[OA\Property(description: 'is_default', type: 'boolean')]
    public ?bool $isDefault;
    #[OA\Property(description: 'is_active', type: 'boolean')]
    public ?bool $isActive;
    #[OA\Property(description: 'fallback_language_id', type: 'integer', nullable: true)]
    public ?int $fallbackLanguageId;
    #[OA\Property(description: 'sort_order', type: 'integer')]
    public ?int $sortOrder;

    /** @var array<string, mixed> */
    private array $mappedFields;

    public function rules(): array
    {
        return [
            'code'                 => 'permit_empty|string|max_length[10]',
            'name'                 => 'permit_empty|string|max_length[50]',
            'native_name'          => 'permit_empty|string|max_length[50]',
            'is_default'           => 'permit_empty|boolean_like',
            'is_active'            => 'permit_empty|boolean_like',
            'fallback_language_id' => 'permit_empty|is_natural_no_zero',
            'sort_order'           => 'permit_empty|integer',
        ];
    }

    protected function map(array $data): void
    {
        $mappedFields = [];

        if (array_key_exists('code', $data)) {
            $this->code = (string) $data['code'];
            $mappedFields['code'] = $this->code;
        } else {
            $this->code = null;
        }

        if (array_key_exists('name', $data)) {
            $this->name = (string) $data['name'];
            $mappedFields['name'] = $this->name;
        } else {
            $this->name = null;
        }

        if (array_key_exists('native_name', $data)) {
            $this->nativeName = (string) $data['native_name'];
            $mappedFields['native_name'] = $this->nativeName;
        } else {
            $this->nativeName = null;
        }

        if (array_key_exists('is_default', $data)) {
            $this->isDefault = filter_var($data['is_default'], FILTER_VALIDATE_BOOLEAN);
            $mappedFields['is_default'] = $this->isDefault;
        } else {
            $this->isDefault = null;
        }

        if (array_key_exists('is_active', $data)) {
            $this->isActive = filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN);
            $mappedFields['is_active'] = $this->isActive;
        } else {
            $this->isActive = null;
        }

        if (array_key_exists('fallback_language_id', $data)) {
            $this->fallbackLanguageId = $data['fallback_language_id'] !== null ? (int) $data['fallback_language_id'] : null;
            $mappedFields['fallback_language_id'] = $this->fallbackLanguageId;
        } else {
            $this->fallbackLanguageId = null;
        }

        if (array_key_exists('sort_order', $data)) {
            $this->sortOrder = (int) $data['sort_order'];
            $mappedFields['sort_order'] = $this->sortOrder;
        } else {
            $this->sortOrder = null;
        }

        $this->mappedFields = $mappedFields;
    }

    public function toArray(): array
    {
        return $this->mappedFields;
    }
}
