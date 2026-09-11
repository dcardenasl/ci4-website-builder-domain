<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Cms;

use App\Interfaces\Cms\CollectionServiceInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Http\ApiController;
use dcardenasl\Ci4ApiCore\Traits\SparseFieldsetTrait;

class PublicCollectionController extends ApiController
{
    use SparseFieldsetTrait;

    /** @var list<string> */
    private const PUBLIC_FIELDS = [
        'id', 'collection_key', 'collection_type', 'is_active', 'requires_approval',
        'enables_categories', 'enables_tags', 'sort_order', 'slug', 'name', 'description',
        'listing_title', 'listing_intro', 'default_meta_title', 'default_meta_description',
        'entry_cta_label', 'localized_slugs', 'is_fallback', 'index_page',
    ];

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

                if (is_string($this->request->getGet('fields')) && trim($this->request->getGet('fields')) !== '') {
                    $data = $this->sparseFilter($data, $this->parseFieldsParam(self::PUBLIC_FIELDS));
                }

                return $this->response->setJSON([
                    'status' => 'success',
                    'data'   => $data,
                ])->setStatusCode(200);
            }
        );
    }
}
