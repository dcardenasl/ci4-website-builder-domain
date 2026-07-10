<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Cms;

use App\Libraries\Cms\PreviewToken;
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
     * List all published pages for a language.
     * Used for sitemap generation and page discovery.
     *
     * @param string $lang Target language code (e.g. 'es')
     */
    public function index(string $lang): ResponseInterface
    {
        return $this->handleRequest(
            function () use ($lang): ResponseInterface {
                $slugRouter = Services::slugRouter();
                $translationResolver = Services::translationResolver();

                // Get all published pages
                $pageModel = model(\App\Models\PageModel::class);
                $pages = $pageModel->where('status', 'published')
                    ->orderBy('sort_order', 'ASC')
                    ->findAll();

                $result = [];

                foreach ($pages as $page) {
                    if ($page instanceof \App\Entities\PageEntity) {
                        // Get slug for this page
                        $slug = $slugRouter->resolveSlug($lang, 'page', (int) $page->id);

                        if (!$slug) {
                            continue; // Skip if no slug found for this language
                        }

                        // Get translation for SEO data
                        $translation = $translationResolver->resolve('page', (int) $page->id, $lang);

                        $result[] = [
                            'slug'                 => $slug,
                            'title'                => $translation['title'] ?? '',
                            'sitemap_priority'    => $page->sitemap_priority ?? 0.5,
                            'sitemap_changefreq'  => $page->sitemap_changefreq ?? 'weekly',
                            'is_in_sitemap'       => $page->is_in_sitemap ?? true,
                            'updated_at'          => $page->updated_at,
                        ];
                    }
                }

                return $this->response->setJSON([
                    'status' => 'success',
                    'data'   => $result,
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
            function () use ($lang, $slug): ResponseInterface {
                $slugRouter = Services::slugRouter();

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

                $pageId = $slugRouter->resolve($lang, 'page', $slug, $preview);

                if ($pageId === null) {
                    throw new NotFoundException(lang('Pages.not_found'));
                }

                // Verify page exists and is published (or a validly-signed preview link)
                $pageModel = model(\App\Models\PageModel::class);
                $page = $pageModel->find($pageId);
                if (!$page || (!$preview && $page->status !== 'published')) {
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

                // Resolve localized slugs for all supported active languages
                $localizedSlugs = [];
                $languageModel = model(\App\Models\LanguageModel::class);
                $languages = $languageModel->where('is_active', 1)->findAll();
                foreach ($languages as $l) {
                    $localizedSlugs[$l->code] = $slugRouter->resolveSlug($l->code, 'page', $pageId);
                }
                $data['localized_slugs'] = $localizedSlugs;

                return $this->response->setJSON([
                    'status' => 'success',
                    'data'   => $data,
                ])->setStatusCode(200);
            }
        );
    }
}
