<?php

declare(strict_types=1);

namespace App\Interfaces\Editor;

use App\DTO\Request\Editor\EditorOwnerDTO;
use App\DTO\Response\Editor\EditorDocumentResponseDTO;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;

interface EditorDocumentReaderInterface
{
    public function load(EditorOwnerDTO $owner, ?SecurityContext $context): EditorDocumentResponseDTO;
}
