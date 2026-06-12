<?php

declare(strict_types=1);

namespace App\DTO\Request\Cms;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'FileTranslationSaveRequest')]
readonly class FileTranslationSaveRequestDTO extends BaseRequestDTO
{
    #[OA\Property(description: 'file_id', type: 'integer')]
    public int $fileId;
    #[OA\Property(description: 'language_id', type: 'integer')]
    public int $languageId;
    #[OA\Property(description: 'alt_text', type: 'string', nullable: true)]
    public ?string $altText;
    #[OA\Property(description: 'caption', type: 'string', nullable: true)]
    public ?string $caption;
    #[OA\Property(description: 'title', type: 'string', nullable: true)]
    public ?string $title;
    #[OA\Property(description: 'credit', type: 'string', nullable: true)]
    public ?string $credit;
    #[OA\Property(description: 'description', type: 'string', nullable: true)]
    public ?string $description;

    public function rules(): array
    {
        return [
            'file_id'     => 'required|is_natural_no_zero',
            'language_id' => 'required|is_natural_no_zero',
            'alt_text'    => 'permit_empty|string|max_length[255]',
            'caption'     => 'permit_empty|string|max_length[500]',
            'title'       => 'permit_empty|string|max_length[255]',
            'credit'      => 'permit_empty|string|max_length[255]',
            'description' => 'permit_empty|string',
        ];
    }

    protected function map(array $data): void
    {
        $this->fileId = (int) ($data['file_id'] ?? 0);
        $this->languageId = (int) ($data['language_id'] ?? 0);
        $this->altText = isset($data['alt_text']) ? (string) $data['alt_text'] : null;
        $this->caption = isset($data['caption']) ? (string) $data['caption'] : null;
        $this->title = isset($data['title']) ? (string) $data['title'] : null;
        $this->credit = isset($data['credit']) ? (string) $data['credit'] : null;
        $this->description = isset($data['description']) ? (string) $data['description'] : null;
    }

    public function toArray(): array
    {
        return [
            'file_id'     => $this->fileId,
            'language_id' => $this->languageId,
            'alt_text'    => $this->altText,
            'caption'     => $this->caption,
            'title'       => $this->title,
            'credit'      => $this->credit,
            'description' => $this->description,
        ];
    }
}
