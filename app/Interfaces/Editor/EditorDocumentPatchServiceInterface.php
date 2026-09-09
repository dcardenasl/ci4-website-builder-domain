<?php

declare(strict_types=1);

namespace App\Interfaces\Editor;

use App\DTO\Editor\EditorOwnerDTO;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Support\OperationResult;

interface EditorDocumentPatchServiceInterface
{
    /**
     * Takes the raw browser payload: the domain owns every limit and every
     * validation rule, so a delivery module never needs the editor's config.
     *
     * @param array<string, mixed> $payload
     */
    public function patch(EditorOwnerDTO $owner, array $payload, ?SecurityContext $context): OperationResult;

    /** The largest request body the contract accepts, for the delivery guard. */
    public function maxPayloadBytes(): int;
}
