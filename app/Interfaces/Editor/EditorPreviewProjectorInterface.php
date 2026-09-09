<?php

declare(strict_types=1);

namespace App\Interfaces\Editor;

use App\DTO\Editor\EditorOwnerDTO;
use App\DTO\Editor\EditorPreviewDocumentDTO;

interface EditorPreviewProjectorInterface
{
    /**
     * Validate, sanitize and resolve an unsaved draft for rendering.
     *
     * Delivery modules hand over the raw payload: the schema rules, the media
     * resolution and the translation fallback all belong to the domain, so the
     * preview surface cannot drift from what saving the same draft would produce.
     *
     * @param array<string, mixed> $payload
     */
    public function project(EditorOwnerDTO $owner, array $payload): EditorPreviewDocumentDTO;
}
