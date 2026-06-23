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

                        $resolvedCollections[] = array_merge($collection->toArray(), [
                            'slug'                     => $resolved['slug'] ?? null,
                            'name'                     => $resolved['name'] ?? '',
                            'description'              => $resolved['description'] ?? null,
                            'listing_title'            => $resolved['listing_title'] ?? null,
                            'listing_intro'            => $resolved['listing_intro'] ?? null,
                            'default_meta_title'       => $resolved['default_meta_title'] ?? null,
                            'default_meta_description' => $resolved['default_meta_description'] ?? null,
                            'localized_slugs'          => $localizedSlugs,
                            'is_fallback'              => $resolved['is_fallback'] ?? false,
                        ]);
                    }
                }

                return $this->response->setJSON([
                    'status' => 'success',
                    'data'   => $resolvedCollections,
                ])->setStatusCode(200);
            }
        );
    }
}
