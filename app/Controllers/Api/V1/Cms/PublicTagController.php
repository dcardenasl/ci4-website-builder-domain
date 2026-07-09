<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Cms;

use App\DTO\Request\Cms\PublicTagIndexRequestDTO;
use App\Interfaces\Cms\TagServiceInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Http\ApiController;

class PublicTagController extends ApiController
{
    protected TagServiceInterface $tagService;

    protected function resolveDefaultService(): TagServiceInterface
    {
        $this->tagService = Services::tagService();

        return $this->tagService;
    }

    /**
     * @param string $lang
     * @param string $collectionKey
     */
    public function index(string $lang, string $collectionKey): ResponseInterface
    {
        return $this->handleRequest(
            function (PublicTagIndexRequestDTO $dto, SecurityContext $context): mixed {
                return $this->tagService->listPublic($dto->lang, $dto->collection_key);
            },
            PublicTagIndexRequestDTO::class,
            ['lang' => $lang, 'collection_key' => $collectionKey]
        );
    }
}
