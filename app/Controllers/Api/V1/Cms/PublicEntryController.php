<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Cms;

use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Exceptions\NotFoundException;
use dcardenasl\Ci4ApiCore\Http\ApiController;

class PublicEntryController extends ApiController
{
    protected function resolveDefaultService(): object
    {
        return Services::entryService();
    }

    /**
     * List active entries of a collection.
     *
     * @param string $lang Target language code (e.g. 'es')
     * @param string $collectionKey Collection identifier (e.g. 'blog')
     */
    public function index(string $lang, string $collectionKey): ResponseInterface
    {
        return $this->handleRequest(
            function () use ($lang, $collectionKey): ResponseInterface {
                $collectionModel = model(\App\Models\CollectionModel::class);
                $collection = $collectionModel->where('collection_key', $collectionKey)
                    ->where('is_active', 1)
                    ->first();

                if (!$collection || !($collection instanceof \App\Entities\CollectionEntity)) {
                    throw new NotFoundException(lang('Collections.not_found'));
                }

                $collectionId = (int) $collection->id;

                $languageModel = model(\App\Models\LanguageModel::class);
                $targetLang = $languageModel->where('code', $lang)->where('is_active', 1)->first();
                if (!$targetLang) {
                    $targetLang = $languageModel->where('is_default', 1)->where('is_active', 1)->first();
                }
                if (!$targetLang || !($targetLang instanceof \App\Entities\LanguageEntity)) {
                    throw new NotFoundException(lang('Entries.language_not_found'));
                }

                // Pagination
                $pageVal = $this->request->getGet('page');
                $page = is_numeric($pageVal) ? (int)$pageVal : 1;
                $perPageVal = $this->request->getGet('per_page');
                $perPage = is_numeric($perPageVal) ? (int)$perPageVal : 20;
                $offset = ($page - 1) * $perPage;

                // Build query via model
                $now = date('Y-m-d H:i:s');
                $entryModel = model(\App\Models\EntryModel::class);
                $builder = $entryModel->where('collection_id', $collectionId)
                    ->where('workflow_status', 'published')
                    ->groupStart()
                        ->where('published_at IS NULL')
                        ->orWhere('published_at <=', $now)
                    ->groupEnd()
                    ->groupStart()
                        ->where('scheduled_at IS NULL')
                        ->orWhere('scheduled_at <=', $now)
                    ->groupEnd();

                $total = $builder->countAllResults(false);
                $entries = $builder->orderBy('sort_order', 'ASC')
                    ->orderBy('created_at', 'DESC')
                    ->findAll($perPage, $offset);

                $resolver = Services::translationResolver();
                $dataList = [];

                foreach ($entries as $entry) {
                    if ($entry instanceof \App\Entities\EntryEntity) {
                        $entryId = (int)$entry->id;
                        $resolved = $resolver->resolve('entry', $entryId, $lang);
                        $dataList[] = array_merge($entry->toArray(), $resolved);
                    }
                }

                return $this->response->setJSON([
                    'status' => 'success',
                    'data'   => $dataList,
                    'pagination' => [
                        'total'     => $total,
                        'page'      => $page,
                        'per_page'  => $perPage,
                    ]
                ])->setStatusCode(200);
            }
        );
    }

    /**
     * Show a single published entry with blocks.
     *
     * @param string $lang Target language code (e.g. 'es')
     * @param string $collectionKey Collection identifier (e.g. 'blog')
     * @param string $slug Entry slug
     */
    public function show(string $lang, string $collectionKey, string $slug): ResponseInterface
    {
        return $this->handleRequest(
            function () use ($lang, $collectionKey, $slug): ResponseInterface {
                $collectionModel = model(\App\Models\CollectionModel::class);
                $collection = $collectionModel->where('collection_key', $collectionKey)
                    ->where('is_active', 1)
                    ->first();

                if (!$collection || !($collection instanceof \App\Entities\CollectionEntity)) {
                    throw new NotFoundException(lang('Collections.not_found'));
                }

                $collectionId = (int)$collection->id;

                $languageModel = model(\App\Models\LanguageModel::class);
                $targetLang = $languageModel->where('code', $lang)->where('is_active', 1)->first();
                if (!$targetLang) {
                    $targetLang = $languageModel->where('is_default', 1)->where('is_active', 1)->first();
                }
                if (!$targetLang || !($targetLang instanceof \App\Entities\LanguageEntity)) {
                    throw new NotFoundException(lang('Entries.language_not_found'));
                }
                $langId = (int)$targetLang->id;

                // Resolve translation slug
                $translationModel = model(\App\Models\EntryTranslationModel::class);
                $entryTranslation = $translationModel->where('slug', $slug)
                    ->where('language_id', $langId)
                    ->first();

                if (!$entryTranslation) {
                    $defaultLang = $languageModel->where('is_default', 1)->where('is_active', 1)->first();
                    if ($defaultLang && !($defaultLang instanceof \App\Entities\LanguageEntity)) {
                        $defaultLang = null;
                    }
                    if ($defaultLang && (int)$defaultLang->id !== $langId) {
                        $entryTranslation = $translationModel->where('slug', $slug)
                            ->where('language_id', (int)$defaultLang->id)
                            ->first();
                    }
                }

                if (!$entryTranslation || !($entryTranslation instanceof \App\Entities\EntryTranslationEntity)) {
                    throw new NotFoundException(lang('Entries.not_found'));
                }

                $entryId = (int)$entryTranslation->entry_id;

                // Find entry and check if active/published
                $now = date('Y-m-d H:i:s');
                $entryModel = model(\App\Models\EntryModel::class);
                $entry = $entryModel->where('id', $entryId)
                    ->where('collection_id', $collectionId)
                    ->where('workflow_status', 'published')
                    ->groupStart()
                        ->where('published_at IS NULL')
                        ->orWhere('published_at <=', $now)
                    ->groupEnd()
                    ->groupStart()
                        ->where('scheduled_at IS NULL')
                        ->orWhere('scheduled_at <=', $now)
                    ->groupEnd()
                    ->first();

                if (!$entry || !($entry instanceof \App\Entities\EntryEntity)) {
                    throw new NotFoundException(lang('Entries.not_found'));
                }

                $resolver = Services::translationResolver();
                $resolved = $resolver->resolve('entry', $entryId, $lang);

                // Load blocks
                $blockSerializer = Services::blockInstanceSerializer();
                $blocks = $blockSerializer->forContent('entry', $entryId, $lang);

                $data = array_merge($entry->toArray(), $resolved);
                $data['blocks'] = $blocks;

                return $this->response->setJSON([
                    'status' => 'success',
                    'data'   => $data,
                ])->setStatusCode(200);
            }
        );
    }
}
