<?php

declare(strict_types=1);

namespace App\DTO\Response\Cms;

use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'LanguageResponse',
    title: 'Language Response',
    required: ["id", "code", "name", "native_name"]
)]
readonly class LanguageResponseDTO implements DataTransferObjectInterface
{
    public function __construct(
        #[OA\Property(description: 'Unique identifier', example: 1)]
        public int $id,
        #[OA\Property(description: 'Language code', example: 'en')]
        public string $code,
        #[OA\Property(description: 'Language name', example: 'English')]
        public string $name,
        #[OA\Property(description: 'Language native name', example: 'English')]
        public string $nativeName,
        #[OA\Property(description: 'Is default language', example: true)]
        public bool $isDefault,
        #[OA\Property(description: 'Is language active', example: true)]
        public bool $isActive,
        #[OA\Property(description: 'Fallback language ID', example: null, nullable: true)]
        public ?int $fallbackLanguageId = null,
        #[OA\Property(description: 'Sort order', example: 0)]
        public int $sortOrder = 0,
        #[OA\Property(property: 'created_at', description: 'Creation timestamp', example: '2026-02-26 12:00:00', nullable: true)]
        public ?string $createdAt = null,
        #[OA\Property(property: 'updated_at', description: 'Last update timestamp', example: '2026-02-26 12:00:00', nullable: true)]
        public ?string $updatedAt = null
    ) {
    }

    public function toArray(): array
    {
        return [
            'id'                   => $this->id,
            'code'                 => $this->code,
            'name'                 => $this->name,
            'native_name'          => $this->nativeName,
            'is_default'           => $this->isDefault,
            'is_active'            => $this->isActive,
            'fallback_language_id' => $this->fallbackLanguageId,
            'sort_order'           => $this->sortOrder,
            'created_at'           => $this->createdAt,
            'updated_at'           => $this->updatedAt,
        ];
    }
}
