<?php

declare(strict_types=1);

namespace App\DTO\Request\Cms;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'RedirectCreateRequest')]
readonly class RedirectCreateRequestDTO extends BaseRequestDTO
{
    #[OA\Property(description: 'old_path', type: 'string')]
    public string $old_path;
    #[OA\Property(description: 'new_url', type: 'string')]
    public string $new_url;
    #[OA\Property(description: 'redirect_type', type: 'integer')]
    public int $redirect_type;
    #[OA\Property(description: 'is_active', type: 'boolean')]
    public bool $is_active;
    #[OA\Property(description: 'hit_count', type: 'integer')]
    public int $hit_count;
    #[OA\Property(description: 'note', type: 'string')]
    public string $note;

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'old_path' => 'required|string|max_length[255]',
            'new_url' => 'required|string|max_length[255]',
            'redirect_type' => 'required|integer',
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
        $this->old_path = (string) ($data['old_path'] ?? '');
        $this->new_url = (string) ($data['new_url'] ?? '');
        $this->redirect_type = (int) ($data['redirect_type'] ?? 0);
        $this->is_active = (bool) ($data['is_active'] ?? false);
        $this->hit_count = (int) ($data['hit_count'] ?? 0);
        $this->note = (string) ($data['note'] ?? '');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'old_path' => $this->old_path,
            'new_url' => $this->new_url,
            'redirect_type' => $this->redirect_type,
            'is_active' => $this->is_active,
            'hit_count' => $this->hit_count,
            'note' => $this->note,
        ];
    }
}
