<?php

declare(strict_types=1);

namespace App\DTO\Request\Cms;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'FileTranslationIndexRequest')]
readonly class FileTranslationIndexRequestDTO extends BaseRequestDTO
{
    public int $page;
    public int $per_page;
    public ?string $search;
    public string $sort;
    public ?int $fileId;

    public function rules(): array
    {
        return [
            'page'      => 'permit_empty|is_natural_no_zero',
            'per_page'  => 'permit_empty|is_natural_no_zero|less_than[1001]',
            'search'    => 'permit_empty|string|max_length[100]',
            'sort'      => 'permit_empty|max_length[100]',
            'file_id'   => 'permit_empty|is_natural_no_zero',
        ];
    }

    protected function map(array $data): void
    {
        $this->page = isset($data['page']) ? (int) $data['page'] : 1;
        $this->per_page = isset($data['per_page']) ? (int) $data['per_page'] : 20;
        $this->search = $data['search'] ?? null;
        $this->sort = (string) ($data['sort'] ?? '');
        $this->fileId = isset($data['file_id']) ? (int) $data['file_id'] : null;
    }

    public function toArray(): array
    {
        return [
            'page' => $this->page,
            'per_page' => $this->per_page,
            'search' => $this->search,
            'sort' => $this->sort,
            'file_id' => $this->fileId,
        ];
    }
}
