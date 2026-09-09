<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Cms;

use App\DTO\Request\Editor\EditorOwnerDTO;
use App\Interfaces\Editor\EditorDocumentReaderInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Http\ApiController;

/**
 * One consistent snapshot of a page or entry for the visual editor.
 *
 * The canvas needs the raw value of every active language at once, which the
 * public serializer cannot give: that one resolves fallback and collapses to a
 * single locale, because a visitor only ever sees one.
 */
class EditorDocumentController extends ApiController
{
    protected EditorDocumentReaderInterface $reader;

    protected function resolveDefaultService(): EditorDocumentReaderInterface
    {
        $this->reader = Services::editorDocumentReader();

        return $this->reader;
    }

    public function showForPage(int $pageId): ResponseInterface
    {
        return $this->document('page', $pageId);
    }

    public function showForEntry(int $entryId): ResponseInterface
    {
        return $this->document('entry', $entryId);
    }

    private function document(string $ownerType, int $ownerId): ResponseInterface
    {
        return $this->handleRequest(
            function (array $dto, SecurityContext $context) use ($ownerType, $ownerId): ResponseInterface {
                // The reader enforces the per-resource permission itself, so a
                // page editor never loads an entry and the route filter is not
                // the only gate.
                $document = $this->reader->load(new EditorOwnerDTO($ownerType, $ownerId), $context);

                return $this->response
                    ->setHeader('Cache-Control', 'no-store')
                    ->setJSON(['status' => 'success', 'data' => $document->toArray()]);
            }
        );
    }
}
