<?php

declare(strict_types=1);

namespace App\DTO\Request\Cms;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'RedirectUpdateRequest')]
readonly class RedirectUpdateRequestDTO extends BaseRequestDTO
{
    #[OA\Property(description: 'old_path', type: 'string', nullable: true)]
    public ?string $old_path;
    #[OA\Property(description: 'new_url', type: 'string', nullable: true)]
    public ?string $new_url;
    #[OA\Property(description: 'redirect_type', type: 'integer', nullable: true)]
    public ?int $redirect_type;
    #[OA\Property(description: 'is_active', type: 'boolean', nullable: true)]
    public ?bool $is_active;
    #[OA\Property(description: 'hit_count', type: 'integer', nullable: true)]
    public ?int $hit_count;
    #[OA\Property(description: 'note', type: 'string', nullable: true)]
    public ?string $note;

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'old_path' => 'permit_empty|string|max_length[255]',
            'new_url' => 'permit_empty|string|max_length[255]',
            'redirect_type' => 'permit_empty|integer',
            'is_active' => 'permit_empty|boolean_like',
            'hit_count' => 'permit_empty|integer',
            'note' => 'permit_empty|string|max_length[255]',
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function map(array $data): void
    {
        $this->old_path = $data['old_path'] ?? null;
        $this->new_url = $data['new_url'] ?? null;
        $this->redirect_type = isset($data['redirect_type']) ? (int) $data['redirect_type'] : null;
        $this->is_active = isset($data['is_active']) ? (bool) $data['is_active'] : null;
        $this->hit_count = isset($data['hit_count']) ? (int) $data['hit_count'] : null;
        $this->note = $data['note'] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'old_path' => $this->old_path,
            'new_url' => $this->new_url,
            'redirect_type' => $this->redirect_type,
            'is_active' => $this->is_active,
            'hit_count' => $this->hit_count,
            'note' => $this->note,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
