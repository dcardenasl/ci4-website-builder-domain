<?php

declare(strict_types=1);

namespace App\DTO\Editor;

use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;

final readonly class EditorDocumentPatchResponseDTO implements DataTransferObjectInterface
{
    /** @param array<string, int> $idMap */
    public function __construct(
        public EditorDocumentResponseDTO $document,
        public bool $conflict = false,
        public array $idMap = [],
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['document' => $this->document->toArray(), 'id_map' => $this->idMap, 'code' => $this->conflict ? 'version_conflict' : 'saved'];
    }
}
