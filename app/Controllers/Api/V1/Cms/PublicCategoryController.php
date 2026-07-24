<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Cms;

use App\DTO\Request\Cms\PublicCategoryIndexRequestDTO;
use App\Interfaces\Cms\CategoryServiceInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Http\ApiController;

class PublicCategoryController extends ApiController
{
    protected CategoryServiceInterface $categoryService;

    protected function resolveDefaultService(): CategoryServiceInterface
    {
        $this->categoryService = Services::categoryService();
        return $this->categoryService;
    }

    /**
     * List active categories for a collection resolved by the request language.
     *
     * @param string $lang          Locale language code (e.g. 'es')
     * @param string $collectionKey Collection key (e.g. 'noticias')
     */
    public function index(string $lang, string $collectionKey): ResponseInterface
    {
        return $this->handleRequest(
            function (PublicCategoryIndexRequestDTO $dto, SecurityContext $context): mixed {
                return $this->categoryService->listPublic($dto->lang, $dto->collection_key);
            },
            PublicCategoryIndexRequestDTO::class,
            ['lang' => $lang, 'collection_key' => $collectionKey]
        );
    }
}
