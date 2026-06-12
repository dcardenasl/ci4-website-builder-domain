<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Cms;

use App\DTO\Request\Cms\PublicEntryIndexRequestDTO;
use App\DTO\Request\Cms\PublicEntryShowRequestDTO;
use App\Interfaces\Cms\EntryServiceInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Http\ApiController;

class PublicEntryController extends ApiController
{
    protected EntryServiceInterface $entryService;

    protected function resolveDefaultService(): EntryServiceInterface
    {
        $this->entryService = Services::entryService();

        return $this->entryService;
    }

    public function index(string $lang, string $collectionKey): ResponseInterface
    {
        return $this->handleRequest(
            function (PublicEntryIndexRequestDTO $dto, SecurityContext $context): mixed {
                return $this->entryService->listPublic($dto);
            },
            PublicEntryIndexRequestDTO::class,
            ['lang' => $lang, 'collection_key' => $collectionKey]
        );
    }

    public function show(string $lang, string $collectionKey, string $slug): ResponseInterface
    {
        return $this->handleRequest(
            function (PublicEntryShowRequestDTO $dto, SecurityContext $context): mixed {
                return $this->entryService->showPublic($dto);
            },
            PublicEntryShowRequestDTO::class,
            ['lang' => $lang, 'collection_key' => $collectionKey, 'slug' => $slug]
        );
    }
}
