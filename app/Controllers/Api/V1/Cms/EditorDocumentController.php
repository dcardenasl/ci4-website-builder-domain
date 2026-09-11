<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Cms;

use App\DTO\Editor\EditorDocumentPatchResponseDTO;
use App\DTO\Editor\EditorOwnerDTO;
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

    public function saveForPage(int $pageId): ResponseInterface
    {
        return $this->patch('page', $pageId);
    }

    public function saveForEntry(int $entryId): ResponseInterface
    {
        return $this->patch('entry', $entryId);
    }

    /**
     * The whole batch in one transaction, behind an optimistic revision check.
     *
     * The raw payload is handed straight to the domain: every limit and every
     * validation rule lives there, so this controller never has to know them.
     */
    private function patch(string $ownerType, int $ownerId): ResponseInterface
    {
        return $this->handleRequest(
            function (array $dto, SecurityContext $context) use ($ownerType, $ownerId): ResponseInterface {
                $payload = $this->request->getJSON(true);
                // A top-level JSON array carries no field names and can never be
                // a patch; treat it as no body rather than passing it on.
                $body = is_array($payload) && ! array_is_list($payload) ? $payload : [];
                /** @var array<string, mixed> $body */
                $result = Services::editorDocumentPatchService()->patch(
                    new EditorOwnerDTO($ownerType, $ownerId),
                    $body,
                    $context,
                );
                $data = $result->data;

                return $this->response
                    ->setStatusCode($result->httpStatus ?? 200)
                    ->setHeader('Cache-Control', 'no-store')
                    ->setJSON([
                        'status' => 'success',
                        'data' => $data instanceof EditorDocumentPatchResponseDTO ? $data->toArray() : [],
                    ]);
            }
        );
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
