<?php

declare(strict_types=1);

namespace App\DTO\Request\Cms;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;

readonly class FormSubmissionUpdateStatusRequestDTO extends BaseRequestDTO
{
    public string $status;

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'status' => 'required|in_list[new,read,replied,spam,archived]',
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function map(array $data): void
    {
        $this->status = (string) ($data['status'] ?? 'read');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return ['status' => $this->status];
    }
}
