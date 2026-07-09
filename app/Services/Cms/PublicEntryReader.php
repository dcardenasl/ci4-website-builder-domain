<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\DTO\Request\Cms\PublicEntryIndexRequestDTO;
use App\DTO\Request\Cms\PublicEntryShowRequestDTO;
use App\Entities\EntryEntity;
use App\Libraries\Cms\FileUrlResolver;
use dcardenasl\Ci4ApiCore\Dto\Common\PayloadResponseDTO;
use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;
use dcardenasl\Ci4ApiCore\Dto\PaginatedResponseDTO;
use dcardenasl\Ci4ApiCore\Exceptions\NotFoundException;

/**
 * Read-optimized public (unauthenticated) entry queries: listing and single-entry
 * lookup by collection + slug, with N+1-safe batch resolution of translations,
 * categories, and tags across the whole result set.
 *
 * Extracted from EntryService, which composes this class for listPublic()/showPublic().
 */
class PublicEntryReader
{
    public function __construct(private FileUrlResolver $fileUrlResolver)
    {
    }

    public function listPublic(PublicEntryIndexRequestDTO $dto): DataTransferObjectInterface
    {
        /** @var \App\Models\CollectionModel $collectionModel */
        $collectionModel = model(\App\Models\CollectionModel::class);
        $collection = $collectionModel
            ->where('collection_key', $dto->collection_key)
            ->where('is_active', 1)
            ->first();

        if (!$collection instanceof \App\Entities\CollectionEntity) {
            throw new NotFoundException(lang('Collections.not_found'));
        }

        ['langId' => $langId, 'defaultLangId' => $defaultLangId] = $this->resolveLanguageIds($dto->lang);

        /** @var \App\Models\EntryModel $entryModel */
        $entryModel = model(\App\Models\EntryModel::class);

        if ($dto->category !== null) {
            /** @var \App\Models\CategoryTranslationModel $catTransModel */
            $catTransModel = model(\App\Models\CategoryTranslationModel::class);
            $catTrans = $catTransModel->where('slug', $dto->category)->first();
            if (!$catTrans instanceof \App\Entities\CategoryTranslationEntity) {
                return PaginatedResponseDTO::fromArray([
                    'data'     => [],
                    'total'    => 0,
                    'page'     => $dto->page,
                    'per_page' => $dto->per_page,
                ]);
            }
            $entryModel->join('cms_entry_categories', 'cms_entry_categories.entry_id = cms_entries.id')
                ->where('cms_entry_categories.category_id', (int) $catTrans->category_id);
        }

        if ($dto->tag !== null) {
            /** @var \App\Models\TagTranslationModel $tagTransModel */
            $tagTransModel = model(\App\Models\TagTranslationModel::class);
            $tagTrans = $tagTransModel->where('slug', $dto->tag)->first();
            if (!$tagTrans instanceof \App\Entities\TagTranslationEntity) {
                return PaginatedResponseDTO::fromArray([
                    'data'     => [],
                    'total'    => 0,
                    'page'     => $dto->page,
                    'per_page' => $dto->per_page,
                ]);
            }
            $entryModel->join('cms_entry_tags', 'cms_entry_tags.entry_id = cms_entries.id')
                ->where('cms_entry_tags.tag_id', (int) $tagTrans->tag_id);
        }

        if ($dto->q !== null) {
            $search = trim($dto->q);
            if ($search !== '') {
                $searchLangIds = [(int) $langId];
                if ($defaultLangId !== $langId) {
                    $searchLangIds[] = (int) $defaultLangId;
                }
                $entryModel->join(
                    'cms_entry_translations search_trans',
                    'search_trans.entry_id = cms_entries.id AND search_trans.language_id IN (' . implode(', ', $searchLangIds) . ')',
                    'left'
                );
                $entryModel->groupStart()
                    ->where('MATCH(search_trans.title, search_trans.excerpt) AGAINST(' . $entryModel->db->escape($search) . ' IN BOOLEAN MODE)', null, false)
                    ->orLike('search_trans.title', $search)
                    ->orLike('search_trans.excerpt', $search)
                ->groupEnd();
                $entryModel->groupBy('cms_entries.id');
            }
        }

        $now    = date('Y-m-d H:i:s');
        $offset = ($dto->page - 1) * $dto->per_page;

        $builder = $entryModel
            ->where('collection_id', (int) $collection->id)
            ->where('workflow_status', 'published')
            ->groupStart()
                ->where('published_at IS NULL')
                ->orWhere('published_at <=', $now)
            ->groupEnd()
            ->groupStart()
                ->where('scheduled_at IS NULL')
                ->orWhere('scheduled_at <=', $now)
            ->groupEnd();

        $total = (int) $builder->countAllResults(false);

        $orderColumn = match ($dto->order_by) {
            'published_at' => 'cms_entries.published_at',
            'created_at'   => 'cms_entries.created_at',
            'title'        => 'title_trans.title',
            default        => 'cms_entries.sort_order',
        };

        if ($dto->order_by === 'title') {
            $builder->join(
                'cms_entry_translations title_trans',
                'title_trans.entry_id = cms_entries.id AND title_trans.language_id = ' . (int) $langId,
                'left'
            );
        }

        $entries = $builder
            ->orderBy($orderColumn, $dto->order_direction)
            ->orderBy('cms_entries.created_at', 'DESC')
            ->findAll($dto->per_page, $offset);

        if (empty($entries)) {
            return PaginatedResponseDTO::fromArray([
                'data'     => [],
                'total'    => $total,
                'page'     => $dto->page,
                'per_page' => $dto->per_page,
            ]);
        }

        $entryIds = [];
        foreach ($entries as $e) {
            if ($e instanceof EntryEntity) {
                $entryIds[] = (int) $e->id;
            }
        }

        $entryTransMap  = $this->batchResolveEntryTranslations($entryIds, $langId, $defaultLangId);
        $categoriesMap  = $this->batchResolveCategoryPivot($entryIds, $langId, $defaultLangId);
        $tagsMap        = $this->batchResolveTagPivot($entryIds, $langId, $defaultLangId);

        $data = [];
        foreach ($entries as $entry) {
            if (!$entry instanceof EntryEntity) {
                continue;
            }
            $entryId = (int) $entry->id;
            $item    = array_merge($entry->toArray(), $entryTransMap[$entryId] ?? []);
            $item['categories'] = $categoriesMap[$entryId] ?? [];
            $item['tags']       = $tagsMap[$entryId] ?? [];
            // Enrich with featured_image_url
            $item = $this->enrichWithFeaturedImageUrl($item);
            $data[] = $item;
        }

        return PaginatedResponseDTO::fromArray([
            'data'     => $data,
            'total'    => (int) $total,
            'page'     => $dto->page,
            'per_page' => $dto->per_page,
        ]);
    }

    public function showPublic(PublicEntryShowRequestDTO $dto): DataTransferObjectInterface
    {
        /** @var \App\Models\CollectionModel $collectionModel */
        $collectionModel = model(\App\Models\CollectionModel::class);
        $collection = $collectionModel
            ->where('collection_key', $dto->collection_key)
            ->where('is_active', 1)
            ->first();

        if (!$collection instanceof \App\Entities\CollectionEntity) {
            throw new NotFoundException(lang('Collections.not_found'));
        }

        ['langId' => $langId, 'defaultLangId' => $defaultLangId] = $this->resolveLanguageIds($dto->lang);

        /** @var \App\Models\EntryTranslationModel $translationModel */
        $translationModel = model(\App\Models\EntryTranslationModel::class);
        $entryTranslation = $translationModel
            ->where('slug', $dto->slug)
            ->where('language_id', $langId)
            ->first();

        if (!$entryTranslation instanceof \App\Entities\EntryTranslationEntity
            && $defaultLangId !== $langId
        ) {
            $entryTranslation = $translationModel
                ->where('slug', $dto->slug)
                ->where('language_id', $defaultLangId)
                ->first();
        }

        if (!$entryTranslation instanceof \App\Entities\EntryTranslationEntity) {
            throw new NotFoundException(lang('Entries.not_found'));
        }

        $entryId = (int) $entryTranslation->entry_id;

        $now    = date('Y-m-d H:i:s');
        /** @var \App\Models\EntryModel $entryModel */
        $entryModel = model(\App\Models\EntryModel::class);
        $entry = $entryModel
            ->where('id', $entryId)
            ->where('collection_id', (int) $collection->id)
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

        if (!$entry instanceof EntryEntity) {
            throw new NotFoundException(lang('Entries.not_found'));
        }

        $entryTransMap = $this->batchResolveEntryTranslations([$entryId], $langId, $defaultLangId);
        $categoriesMap = $this->batchResolveCategoryPivot([$entryId], $langId, $defaultLangId);
        $tagsMap       = $this->batchResolveTagPivot([$entryId], $langId, $defaultLangId);

        $blockSerializer = service('blockInstanceSerializer');
        $blocks = $blockSerializer->forContent('entry', $entryId, $dto->lang);

        $data               = array_merge($entry->toArray(), $entryTransMap[$entryId] ?? []);
        $data['categories'] = $categoriesMap[$entryId] ?? [];
        $data['tags']       = $tagsMap[$entryId] ?? [];
        $data['blocks']     = $blocks;

        // Enrich with featured_image_url
        $data = $this->enrichWithFeaturedImageUrl($data);

        // Get all translations of this entry to construct localized slugs
        /** @var list<\App\Entities\EntryTranslationEntity> $allTranslations */
        $allTranslations = $translationModel->where('entry_id', $entryId)->findAll();
        /** @var \App\Models\LanguageModel $langModel */
        $langModel = model(\App\Models\LanguageModel::class);
        /** @var list<\App\Entities\LanguageEntity> $activeLanguages */
        $activeLanguages = $langModel->where('is_active', 1)->findAll();
        $langCodeMap = [];
        foreach ($activeLanguages as $al) {
            $langCodeMap[$al->id] = $al->code;
        }

        $localizedSlugs = [];
        foreach ($allTranslations as $at) {
            $code = $langCodeMap[$at->language_id] ?? null;
            if ($code !== null) {
                $localizedSlugs[$code] = $at->slug;
            }
        }
        $data['localized_slugs'] = $localizedSlugs;

        return PayloadResponseDTO::fromArray($data);
    }

    /**
     * Resolves target and default language IDs in a single query.
     *
     * @return array{langId: int, defaultLangId: int}
     */
    private function resolveLanguageIds(string $langCode): array
    {
        /** @var \App\Models\LanguageModel $langModel */
        $langModel = model(\App\Models\LanguageModel::class);

        $rows = $langModel
            ->groupStart()
                ->where('code', $langCode)
                ->orWhere('is_default', 1)
            ->groupEnd()
            ->where('is_active', 1)
            ->findAll();

        $langId        = null;
        $defaultLangId = null;

        foreach ($rows as $row) {
            if (!$row instanceof \App\Entities\LanguageEntity) {
                continue;
            }
            if ($row->code === $langCode) {
                $langId = (int) $row->id;
            }
            if ((int) $row->is_default === 1) {
                $defaultLangId = (int) $row->id;
            }
        }

        if ($langId === null && $defaultLangId === null) {
            throw new NotFoundException(lang('Entries.language_not_found'));
        }

        $resolvedLangId    = $langId ?? $defaultLangId;
        $resolvedDefaultId = $defaultLangId ?? $langId;

        return ['langId' => $resolvedLangId, 'defaultLangId' => $resolvedDefaultId];
    }

    /**
     * @param  list<int>  $entryIds
     * @return array<int, array<string, mixed>>
     */
    private function batchResolveEntryTranslations(array $entryIds, int $langId, int $defaultLangId): array
    {
        if (empty($entryIds)) {
            return [];
        }

        /** @var \App\Models\EntryTranslationModel $model */
        $model = model(\App\Models\EntryTranslationModel::class);
        $rows  = $model
            ->whereIn('entry_id', $entryIds)
            ->whereIn('language_id', array_unique([$langId, $defaultLangId]))
            ->findAll();

        $map = [];
        foreach ($rows as $row) {
            if (!$row instanceof \App\Entities\EntryTranslationEntity) {
                continue;
            }
            $eid = (int) $row->entry_id;
            $lid = (int) $row->language_id;
            if (!isset($map[$eid]) || $lid === $langId) {
                $map[$eid] = [
                    'slug'             => $row->slug,
                    'title'            => $row->title,
                    'excerpt'          => $row->excerpt,
                    'featured_file_id' => $row->featured_file_id,
                    'featured_image_url' => $row->featured_image_url,
                    'meta_title'       => $row->meta_title,
                    'meta_description' => $row->meta_description,
                    'og_image_file_id' => $row->og_image_file_id,
                    'og_type'          => $row->og_type,
                    'canonical_url'    => $row->canonical_url,
                    'robots'           => $row->robots,
                    'schema_data'      => $row->schema_data,
                    'is_fallback'      => $lid !== $langId,
                ];
            }
        }

        return $map;
    }

    /**
     * @param  list<int>  $entryIds
     * @return array<int, list<array<string, mixed>>>
     */
    private function batchResolveCategoryPivot(array $entryIds, int $langId, int $defaultLangId): array
    {
        if (empty($entryIds)) {
            return [];
        }

        $langIds          = array_unique([$langId, $defaultLangId]);
        $langPlaceholders = implode(',', array_fill(0, count($langIds), '?'));
        $entryPlaceholders = implode(',', array_fill(0, count($entryIds), '?'));

        $sql = "
            SELECT ec.entry_id, ec.category_id, ec.sort_order,
                   ct.name, ct.slug, ct.description, ct.language_id
            FROM cms_entry_categories ec
            LEFT JOIN cms_category_translations ct
                ON ct.category_id = ec.category_id
               AND ct.language_id IN ({$langPlaceholders})
            WHERE ec.entry_id IN ({$entryPlaceholders})
            ORDER BY ec.entry_id ASC, ec.sort_order ASC, ct.language_id DESC
        ";

        $db     = \Config\Database::connect();
        $result = $db->query($sql, array_merge($langIds, $entryIds));

        if (!$result instanceof \CodeIgniter\Database\BaseResult) {
            return [];
        }

        $map      = [];
        $seenCats = [];

        foreach ($result->getResultArray() as $row) {
            $eid   = (int) $row['entry_id'];
            $catId = (int) $row['category_id'];
            $lid   = (int) ($row['language_id'] ?? 0);

            if (!isset($map[$eid])) {
                $map[$eid]      = [];
                $seenCats[$eid] = [];
            }

            if (isset($seenCats[$eid][$catId]) && $lid !== $langId) {
                continue;
            }

            $seenCats[$eid][$catId] = true;
            $map[$eid][] = [
                'id'          => $catId,
                'name'        => $row['name'],
                'slug'        => $row['slug'],
                'description' => $row['description'],
                'is_fallback' => $lid !== $langId,
            ];
        }

        return $map;
    }

    /**
     * @param  list<int>  $entryIds
     * @return array<int, list<array<string, mixed>>>
     */
    private function batchResolveTagPivot(array $entryIds, int $langId, int $defaultLangId): array
    {
        if (empty($entryIds)) {
            return [];
        }

        $langIds           = array_unique([$langId, $defaultLangId]);
        $langPlaceholders  = implode(',', array_fill(0, count($langIds), '?'));
        $entryPlaceholders = implode(',', array_fill(0, count($entryIds), '?'));

        $sql = "
            SELECT et.entry_id, et.tag_id,
                   tt.name, tt.slug, tt.language_id
            FROM cms_entry_tags et
            LEFT JOIN cms_tag_translations tt
                ON tt.tag_id = et.tag_id
               AND tt.language_id IN ({$langPlaceholders})
            WHERE et.entry_id IN ({$entryPlaceholders})
            ORDER BY et.entry_id ASC, et.tag_id ASC, tt.language_id DESC
        ";

        $db     = \Config\Database::connect();
        $result = $db->query($sql, array_merge($langIds, $entryIds));

        if (!$result instanceof \CodeIgniter\Database\BaseResult) {
            return [];
        }

        $map      = [];
        $seenTags = [];

        foreach ($result->getResultArray() as $row) {
            $eid   = (int) $row['entry_id'];
            $tagId = (int) $row['tag_id'];
            $lid   = (int) ($row['language_id'] ?? 0);

            if (!isset($map[$eid])) {
                $map[$eid]       = [];
                $seenTags[$eid]  = [];
            }

            if (isset($seenTags[$eid][$tagId]) && $lid !== $langId) {
                continue;
            }

            $seenTags[$eid][$tagId] = true;
            $map[$eid][] = [
                'id'          => $tagId,
                'name'        => $row['name'],
                'slug'        => $row['slug'],
                'is_fallback' => $lid !== $langId,
            ];
        }

        return $map;
    }

    /**
     * Enrich an entry array with featured_image_url if featured_file_id is present.
     *
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    private function enrichWithFeaturedImageUrl(array $item): array
    {
        $item['featured_image_url'] = $this->fileUrlResolver->resolveUrlValue(
            $item['featured_file_id'] ?? null,
            isset($item['featured_image_url']) ? (string) $item['featured_image_url'] : null
        );

        if (! isset($item['og_image_url'])) {
            $item['og_image_url'] = $this->fileUrlResolver->resolveUrlValue(
                $item['og_image_file_id'] ?? null,
                isset($item['og_image_url']) ? (string) $item['og_image_url'] : null
            );
        }

        return $item;
    }
}
