<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Cms;

use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Exceptions\NotFoundException;
use dcardenasl\Ci4ApiCore\Http\ApiController;

class PublicPageController extends ApiController
{
    protected function resolveDefaultService(): object
    {
        return Services::pageService();
    }

    /**
     * Resolve a public page by language and slug.
     *
     * @param string $lang Target language code (e.g. 'es')
     * @param string $slug Target page slug (e.g. 'nosotros/vision')
     */
    public function show(string $lang, string $slug): ResponseInterface
    {
        return $this->handleRequest(
            function () use ($lang, $slug): ResponseInterface {
                $slugRouter = Services::slugRouter();
                $pageId = $slugRouter->resolve($lang, 'page', $slug);

                if ($pageId === null) {
                    throw new NotFoundException(lang('Pages.not_found'));
                }

                // Verify page exists and is published
                $pageModel = model(\App\Models\PageModel::class);
                $page = $pageModel->find($pageId);
                if (!$page || $page->status !== 'published') {
                    throw new NotFoundException(lang('Pages.not_found'));
                }

                $translationResolver = Services::translationResolver();
                $translation = $translationResolver->resolve('page', $pageId, $lang);

                // Load blocks
                $blockSerializer = Services::blockInstanceSerializer();
                $blocks = $blockSerializer->forContent('page', $pageId, $lang);

                // Build response structure
                $data = array_merge($page->toArray(), $translation);
                $data['blocks'] = $blocks;

                return $this->response->setJSON([
                    'status' => 'success',
                    'data'   => $data,
                ])->setStatusCode(200);
            }
        );
    }
}
