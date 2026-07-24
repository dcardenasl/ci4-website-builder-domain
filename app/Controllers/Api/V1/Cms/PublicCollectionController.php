<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Cms;

use App\Interfaces\Cms\CollectionServiceInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Http\ApiController;

class PublicCollectionController extends ApiController
{
    protected CollectionServiceInterface $collectionService;

    protected function resolveDefaultService(): CollectionServiceInterface
    {
        $this->collectionService = Services::collectionService();

        return $this->collectionService;
    }

    /**
     * List all active collections resolved by the request language.
     *
     * @param string $lang Locale language code (e.g. 'es')
     */
    public function index(string $lang): ResponseInterface
    {
        return $this->handleRequest(
            function (array $dto, SecurityContext $context) use ($lang): ResponseInterface {
                $data = $this->collectionService->listPublic($lang);

                return $this->response->setJSON([
                    'status' => 'success',
                    'data'   => $data,
                ])->setStatusCode(200);
            }
        );
    }
}
