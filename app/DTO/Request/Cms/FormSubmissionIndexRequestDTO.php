<?php

declare(strict_types=1);

namespace App\DTO\Request\Cms;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;

readonly class FormSubmissionIndexRequestDTO extends BaseRequestDTO
{
    public int     $page;
    public int     $per_page;
    public ?string $status;
    public ?string $form_key;

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'page'     => 'permit_empty|integer|greater_than[0]',
            'per_page' => 'permit_empty|integer|greater_than[0]|less_than_equal_to[100]',
            'status'   => 'permit_empty|in_list[new,read,replied,spam,archived]',
            'form_key' => 'permit_empty|string|max_length[50]',
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function map(array $data): void
    {
        $this->page     = max(1, (int) ($data['page'] ?? 1));
        $this->per_page = min(100, max(1, (int) ($data['per_page'] ?? 25)));
        $this->status   = isset($data['status']) && $data['status'] !== '' ? (string) $data['status'] : null;
        $this->form_key = isset($data['form_key']) && $data['form_key'] !== '' ? (string) $data['form_key'] : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'page'     => $this->page,
            'per_page' => $this->per_page,
            'status'   => $this->status,
            'form_key' => $this->form_key,
        ];
    }
}
