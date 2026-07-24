<?php

declare(strict_types=1);

namespace App\DTO\Response\Cms;

use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'FileTranslationResponse',
    title: 'File Translation Response',
    required: ["id", "file_id", "language_id"]
)]
readonly class FileTranslationResponseDTO implements DataTransferObjectInterface
{
    public function __construct(
        #[OA\Property(description: 'Unique identifier', example: 1)]
        public int $id,
        #[OA\Property(description: 'File ID', example: 1)]
        public int $fileId,
        #[OA\Property(description: 'Language ID', example: 1)]
        public int $languageId,
        #[OA\Property(description: 'Alt text', example: 'Image of a cat', nullable: true)]
        public ?string $altText = null,
        #[OA\Property(description: 'Caption', example: 'A fluffy kitten', nullable: true)]
        public ?string $caption = null,
        #[OA\Property(description: 'Title', example: 'Cat', nullable: true)]
        public ?string $title = null,
        #[OA\Property(description: 'Credit', example: 'John Doe', nullable: true)]
        public ?string $credit = null,
        #[OA\Property(description: 'Description', example: 'Detailed description', nullable: true)]
        public ?string $description = null,
        #[OA\Property(property: 'created_at', description: 'Creation timestamp', example: '2026-02-26 12:00:00', nullable: true)]
        public ?string $createdAt = null,
        #[OA\Property(property: 'updated_at', description: 'Last update timestamp', example: '2026-02-26 12:00:00', nullable: true)]
        public ?string $updatedAt = null
    ) {
    }

    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'file_id'     => $this->fileId,
            'language_id' => $this->languageId,
            'alt_text'    => $this->altText,
            'caption'     => $this->caption,
            'title'       => $this->title,
            'credit'      => $this->credit,
            'description' => $this->description,
            'created_at'  => $this->createdAt,
            'updated_at'  => $this->updatedAt,
        ];
    }
}
