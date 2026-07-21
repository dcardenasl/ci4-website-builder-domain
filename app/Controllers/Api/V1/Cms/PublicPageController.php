<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Cms;

use App\Interfaces\Cms\PageServiceInterface;
use App\Libraries\Cms\PreviewToken;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Http\ApiController;

class PublicPageController extends ApiController
{
    protected PageServiceInterface $pageService;

    protected function resolveDefaultService(): PageServiceInterface
    {
        $this->pageService = Services::pageService();

        return $this->pageService;
    }

    /**
     * List all published pages for a language.
     * Used for sitemap generation and page discovery.
     *
     * @param string $lang Target language code (e.g. 'es')
     */
    public function index(string $lang): ResponseInterface
    {
        return $this->handleRequest(
            function (array $dto, SecurityContext $context) use ($lang): ResponseInterface {
                $data = $this->pageService->listPublic($lang);

                return $this->response->setJSON([
                    'status' => 'success',
                    'data'   => $data,
                ])->setStatusCode(200);
            }
        );
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
            function (array $dto, SecurityContext $context) use ($lang, $slug): ResponseInterface {
                // Slug resolution itself is published-only (findPageBySlugAndParent),
                // so a signed preview link must be verified against lang+slug —
                // before we even know the page ID — to be allowed to bypass it.
                $previewExpiresRaw = $this->request->getGet('preview_expires');
                $previewSigRaw = $this->request->getGet('preview_sig');
                $preview = $this->request->getGet('preview') === '1'
                    && PreviewToken::verify(
                        'page',
                        $lang . ':' . trim($slug, '/'),
                        is_string($previewExpiresRaw) ? $previewExpiresRaw : null,
                        is_string($previewSigRaw) ? $previewSigRaw : null
                    );

                $data = $this->pageService->showPublic($lang, $slug, $preview);

                return $this->response->setJSON([
                    'status' => 'success',
                    'data'   => $data,
                ])->setStatusCode(200);
            }
        );
    }
}
