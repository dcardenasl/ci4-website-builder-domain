<?php

declare(strict_types=1);

namespace App\DTO\Response\Cms;

use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'RedirectResponse',
    title: 'Redirect Response',
    required: ["id","old_path","new_url","redirect_type","is_active"]
)]
final readonly class RedirectResponseDTO implements DataTransferObjectInterface
{
    public function __construct(
        #[OA\Property(description: 'Unique identifier', example: 1)]
        public int $id,
        #[OA\Property(description: 'old_path', type: 'string')]
        public string $old_path,
        #[OA\Property(description: 'new_url', type: 'string')]
        public string $new_url,
        #[OA\Property(description: 'redirect_type', type: 'integer')]
        public int $redirect_type,
        #[OA\Property(description: 'is_active', type: 'boolean')]
        public bool $is_active,
        #[OA\Property(description: 'hit_count', type: 'integer')]
        public int $hit_count,
        #[OA\Property(description: 'note', type: 'string')]
        public string $note,
        #[OA\Property(property: 'created_at', description: 'Creation timestamp', example: '2026-02-26 12:00:00', nullable: true)]
        public ?string $createdAt = null,
        #[OA\Property(property: 'updated_at', description: 'Last update timestamp', example: '2026-02-26 12:00:00', nullable: true)]
        public ?string $updatedAt = null
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new static(
            id: (int) ($data['id'] ?? 0),
            old_path: (string) ($data['old_path'] ?? ''),
            new_url: (string) ($data['new_url'] ?? ''),
            redirect_type: (int) ($data['redirect_type'] ?? 0),
            is_active: (bool) ($data['is_active'] ?? false),
            hit_count: (int) ($data['hit_count'] ?? 0),
            note: (string) ($data['note'] ?? ''),
            createdAt: DateValue::toString($data['created_at'] ?? null),
            updatedAt: DateValue::toString($data['updated_at'] ?? null),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'old_path' => $this->old_path,
            'new_url' => $this->new_url,
            'redirect_type' => $this->redirect_type,
            'is_active' => $this->is_active,
            'hit_count' => $this->hit_count,
            'note' => $this->note,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
