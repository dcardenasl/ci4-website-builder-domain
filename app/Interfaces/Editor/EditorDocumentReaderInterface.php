<?php

declare(strict_types=1);

namespace App\Interfaces\Editor;

use App\DTO\Editor\EditorDocumentResponseDTO;
use App\DTO\Editor\EditorOwnerDTO;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;

interface EditorDocumentReaderInterface
{
    public function load(EditorOwnerDTO $owner, ?SecurityContext $context): EditorDocumentResponseDTO;
}
