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

                // Category filtering
                $categorySlug = $this->request->getGet('category');
                if (!empty($categorySlug)) {
                    $db = \Config\Database::connect();
                    $categoryRes = $db->table('cms_category_translations')
                        ->where('slug', $categorySlug)
                        ->get();
                    $categoryTrans = $categoryRes instanceof \CodeIgniter\Database\ResultInterface ? $categoryRes->getRowArray() : null;
                    if ($categoryTrans) {
                        $entryModel->join('cms_entry_categories', 'cms_entry_categories.entry_id = cms_entries.id')
                            ->where('cms_entry_categories.category_id', (int) $categoryTrans['category_id']);
                    } else {
                        // force empty results
                        $entryModel->where('1 = 0');
                    }
                }

                // Tag filtering
                $tagSlug = $this->request->getGet('tag');
                if (!empty($tagSlug)) {
                    $db = \Config\Database::connect();
                    $tagRes = $db->table('cms_tag_translations')
                        ->where('slug', $tagSlug)
                        ->get();
                    $tagTrans = $tagRes instanceof \CodeIgniter\Database\ResultInterface ? $tagRes->getRowArray() : null;
                    if ($tagTrans) {
                        $entryModel->join('cms_entry_tags', 'cms_entry_tags.entry_id = cms_entries.id')
                            ->where('cms_entry_tags.tag_id', (int) $tagTrans['tag_id']);
                    } else {
                        // force empty results
                        $entryModel->where('1 = 0');
                    }
                }

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
                $entries = $builder->orderBy('cms_entries.sort_order', 'ASC')
                    ->orderBy('cms_entries.created_at', 'DESC')
                    ->findAll($perPage, $offset);

                $resolver = Services::translationResolver();
                $dataList = [];

                foreach ($entries as $entry) {
                    if ($entry instanceof \App\Entities\EntryEntity) {
                        $entryId = (int)$entry->id;
                        $resolved = $resolver->resolve('entry', $entryId, $lang);

                        // Resolve associated categories
                        $db = \Config\Database::connect();
                        $catsRes = $db->table('cms_entry_categories')
                            ->select('category_id')
                            ->where('entry_id', $entryId)
                            ->orderBy('sort_order', 'ASC')
                            ->get();
                        $cats = $catsRes instanceof \CodeIgniter\Database\ResultInterface ? $catsRes->getResultArray() : [];
                        $resolvedCats = [];
                        foreach ($cats as $cat) {
                            $catTrans = $resolver->resolve('category', (int)$cat['category_id'], $lang);
                            $resolvedCats[] = array_merge(['id' => (int)$cat['category_id']], $catTrans);
                        }

                        // Resolve associated tags
                        $tagsRes = $db->table('cms_entry_tags')
                            ->select('tag_id')
                            ->where('entry_id', $entryId)
                            ->get();
                        $tags = $tagsRes instanceof \CodeIgniter\Database\ResultInterface ? $tagsRes->getResultArray() : [];
                        $resolvedTags = [];
                        foreach ($tags as $t) {
                            $tagTrans = $resolver->resolve('tag', (int)$t['tag_id'], $lang);
                            $resolvedTags[] = array_merge(['id' => (int)$t['tag_id']], $tagTrans);
                        }

                        $itemData = array_merge($entry->toArray(), $resolved);
                        $itemData['categories'] = $resolvedCats;
                        $itemData['tags'] = $resolvedTags;
                        $dataList[] = $itemData;
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

                // Resolve associated categories
                $db = \Config\Database::connect();
                $catsRes = $db->table('cms_entry_categories')
                    ->select('category_id')
                    ->where('entry_id', $entryId)
                    ->orderBy('sort_order', 'ASC')
                    ->get();
                $cats = $catsRes instanceof \CodeIgniter\Database\ResultInterface ? $catsRes->getResultArray() : [];
                $resolvedCats = [];
                foreach ($cats as $cat) {
                    $catTrans = $resolver->resolve('category', (int)$cat['category_id'], $lang);
                    $resolvedCats[] = array_merge(['id' => (int)$cat['category_id']], $catTrans);
                }

                // Resolve associated tags
                $tagsRes = $db->table('cms_entry_tags')
                    ->select('tag_id')
                    ->where('entry_id', $entryId)
                    ->get();
                $tags = $tagsRes instanceof \CodeIgniter\Database\ResultInterface ? $tagsRes->getResultArray() : [];
                $resolvedTags = [];
                foreach ($tags as $t) {
                    $tagTrans = $resolver->resolve('tag', (int)$t['tag_id'], $lang);
                    $resolvedTags[] = array_merge(['id' => (int)$t['tag_id']], $tagTrans);
                }

                $data = array_merge($entry->toArray(), $resolved);
                $data['blocks'] = $blocks;
                $data['categories'] = $resolvedCats;
                $data['tags'] = $resolvedTags;

                return $this->response->setJSON([
                    'status' => 'success',
                    'data'   => $data,
                ])->setStatusCode(200);
            }
        );
    }
}
