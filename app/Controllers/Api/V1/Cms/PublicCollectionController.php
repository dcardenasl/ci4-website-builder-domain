<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Cms;

use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Http\ApiController;

class PublicCollectionController extends ApiController
{
    protected function resolveDefaultService(): object
    {
        return Services::collectionService();
    }

    /**
     * List all active collections resolved by the request language.
     *
     * @param string $lang Locale language code (e.g. 'es')
     */
    public function index(string $lang): ResponseInterface
    {
        return $this->handleRequest(
            function () use ($lang): ResponseInterface {
                $collectionModel = model(\App\Models\CollectionModel::class);
                $collections = $collectionModel->where('is_active', 1)
                    ->orderBy('sort_order', 'ASC')
                    ->findAll();

                $translationResolver = Services::translationResolver();
                $languageModel = model(\App\Models\LanguageModel::class);
                $activeLanguages = $languageModel->where('is_active', 1)->findAll();

                $resolvedCollections = [];
                foreach ($collections as $collection) {
                    if ($collection instanceof \App\Entities\CollectionEntity) {
                        $resolved = $translationResolver->resolve('collection', (int) $collection->id, $lang);
                        $indexPage = $this->resolveCollectionIndexPage((int) $collection->id);
                        $indexPageData = null;
                        if ($indexPage instanceof \App\Entities\PageEntity) {
                            $indexPageData = $this->resolveIndexPageData((int) $indexPage->id);
                        }
                        $localizedSlugs = [];
                        foreach ($activeLanguages as $activeLanguage) {
                            if ($activeLanguage instanceof \App\Entities\LanguageEntity) {
                                $translation = $translationResolver->resolve('collection', (int) $collection->id, $activeLanguage->code);
                                $slug = $translation['slug'] ?? null;
                                if (is_string($slug) && $slug !== '') {
                                    $localizedSlugs[$activeLanguage->code] = $slug;
                                }
                            }
                        }

                        $collectionPayload = array_merge($collection->toArray(), [
                            'slug'                     => $resolved['slug'] ?? null,
                            'name'                     => $resolved['name'] ?? '',
                            'description'              => $resolved['description'] ?? null,
                            'listing_title'            => $resolved['listing_title'] ?? null,
                            'listing_intro'            => $resolved['listing_intro'] ?? null,
                            'default_meta_title'       => $resolved['default_meta_title'] ?? null,
                            'default_meta_description' => $resolved['default_meta_description'] ?? null,
                            'localized_slugs'          => $localizedSlugs,
                            'is_fallback'              => $resolved['is_fallback'] ?? false,
                            'index_page'               => $indexPageData,
                        ]);

                        $collectionPayload['listing_title'] = collection_display_title($collectionPayload);
                        $collectionPayload['listing_intro'] = collection_display_intro($collectionPayload);

                        $resolvedCollections[] = $collectionPayload;
                    }
                }

                return $this->response->setJSON([
                    'status' => 'success',
                    'data'   => $resolvedCollections,
                ])->setStatusCode(200);
            }
        );
    }

    /**
     * @return \App\Entities\PageEntity|null
     */
    private function resolveCollectionIndexPage(int $collectionId): ?\App\Entities\PageEntity
    {
        $pageModel = model(\App\Models\PageModel::class);
        $page = $pageModel
            ->where('collection_id', $collectionId)
            ->where('page_type', 'collection_index')
            ->where('status', 'published')
            ->first();

        return $page instanceof \App\Entities\PageEntity ? $page : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveIndexPageData(int $pageId): array
    {
        $slugRouter = Services::slugRouter();
        $languageModel = model(\App\Models\LanguageModel::class);
        $languages = $languageModel->where('is_active', 1)->findAll();

        $localizedSlugs = [];
        $localizedUrls = [];
        foreach ($languages as $language) {
            if (! $language instanceof \App\Entities\LanguageEntity) {
                continue;
            }

            $slug = $slugRouter->resolveSlug($language->code, 'page', $pageId);
            if (is_string($slug) && $slug !== '') {
                $localizedSlugs[$language->code] = $slug;
                $localizedUrls[$language->code] = site_url('/' . $language->code . '/' . ltrim($slug, '/'));
            }
        }

        return [
            'id' => $pageId,
            'localized_slugs' => $localizedSlugs,
            'localized_urls' => $localizedUrls,
        ];
    }
}
