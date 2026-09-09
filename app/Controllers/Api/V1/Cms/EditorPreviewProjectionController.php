<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Cms;

use App\DTO\Editor\EditorOwnerDTO;
use App\Interfaces\Editor\EditorPreviewProjectorInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Http\ApiController;

/**
 * Projects an unsaved editor draft into the shape the public renderer consumes.
 *
 * The public site owns the templates but not the block schema, so it asks for
 * the draft to be validated, sanitized, fallback-resolved and media-resolved
 * here — exactly as a saved document would be — and then renders the result.
 *
 * The draft arrives from a browser through the public site, so nothing in it is
 * trusted: the projector re-derives everything from the catalog and refuses any
 * block that does not belong to the document.
 */
class EditorPreviewProjectionController extends ApiController
{
    protected EditorPreviewProjectorInterface $projector;

    protected function resolveDefaultService(): EditorPreviewProjectorInterface
    {
        $this->projector = Services::editorPreviewProjector();

        return $this->projector;
    }

    public function project(string $ownerSegment, int $ownerId): ResponseInterface
    {
        // The route carries the plural used in URLs; the domain speaks singular.
        $ownerType = $ownerSegment === 'entries' ? 'entry' : 'page';

        return $this->handleRequest(
            function (array $dto, SecurityContext $context) use ($ownerType, $ownerId): ResponseInterface {
                $payload = $this->request->getJSON(true);
                $body = is_array($payload) && ! array_is_list($payload) ? $payload : [];
                /** @var array<string, mixed> $body */
                $document = $this->projector->project(new EditorOwnerDTO($ownerType, $ownerId), $body);

                return $this->response
                    ->setHeader('Cache-Control', 'no-store')
                    ->setJSON(['status' => 'success', 'data' => $document->toArray()]);
            }
        );
    }
}
