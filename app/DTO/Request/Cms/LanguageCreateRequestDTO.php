<?php

declare(strict_types=1);

namespace App\DTO\Request\Cms;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'LanguageCreateRequest')]
readonly class LanguageCreateRequestDTO extends BaseRequestDTO
{
    #[OA\Property(description: 'code', type: 'string')]
    public string $code;
    #[OA\Property(description: 'name', type: 'string')]
    public string $name;
    #[OA\Property(description: 'native_name', type: 'string')]
    public string $nativeName;
    #[OA\Property(description: 'is_default', type: 'boolean')]
    public bool $isDefault;
    #[OA\Property(description: 'is_active', type: 'boolean')]
    public bool $isActive;
    #[OA\Property(description: 'fallback_language_id', type: 'integer', nullable: true)]
    public ?int $fallbackLanguageId;
    #[OA\Property(description: 'sort_order', type: 'integer')]
    public int $sortOrder;

    public function rules(): array
    {
        return [
            'code'                 => 'required|string|max_length[10]|is_unique[cms_languages.code]',
            'name'                 => 'required|string|max_length[50]',
            'native_name'          => 'required|string|max_length[50]',
            'is_default'           => 'permit_empty|boolean_like',
            'is_active'            => 'permit_empty|boolean_like',
            'fallback_language_id' => 'permit_empty|is_natural_no_zero',
            'sort_order'           => 'permit_empty|integer',
        ];
    }

    protected function map(array $data): void
    {
        $this->code = (string) ($data['code'] ?? '');
        $this->name = (string) ($data['name'] ?? '');
        $this->nativeName = (string) ($data['native_name'] ?? '');
        $this->isDefault = filter_var($data['is_default'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $this->isActive = filter_var($data['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $this->fallbackLanguageId = isset($data['fallback_language_id']) ? (int) $data['fallback_language_id'] : null;
        $this->sortOrder = isset($data['sort_order']) ? (int) $data['sort_order'] : 0;
    }

    public function toArray(): array
    {
        return [
            'code'                 => $this->code,
            'name'                 => $this->name,
            'native_name'          => $this->nativeName,
            'is_default'           => $this->isDefault,
            'is_active'            => $this->isActive,
            'fallback_language_id' => $this->fallbackLanguageId,
            'sort_order'           => $this->sortOrder,
        ];
    }
}
