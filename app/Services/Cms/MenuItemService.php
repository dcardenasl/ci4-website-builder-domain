<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\Entities\MenuItemEntity;
use App\Interfaces\Cms\MenuItemServiceInterface;
use App\Traits\Services\HasDeferredTranslations;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Exceptions\ValidationException;
use dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface;
use dcardenasl\Ci4ApiCore\Repositories\RepositoryInterface;
use dcardenasl\Ci4ApiCore\Services\BaseCrudService;

/**
 * @extends BaseCrudService<MenuItemEntity>
 */
class MenuItemService extends BaseCrudService implements MenuItemServiceInterface
{
    use HasDeferredTranslations;

    private \App\Libraries\Cms\CacheInvalidationClient $cacheInvalidator;

    private \App\Libraries\Cms\TranslationResolver $translationResolver;

    private \App\Libraries\Cms\SlugRouter $slugRouter;

    private ?\App\Libraries\Cms\TranslationSynchronizer $translationSynchronizer;

    /**
     * @param RepositoryInterface<MenuItemEntity> $menuItemRepository
     */
    public function __construct(
        RepositoryInterface $menuItemRepository,
        ResponseMapperInterface $responseMapper,
        \App\Libraries\Cms\CacheInvalidationClient $cacheInvalidator,
        \App\Libraries\Cms\TranslationResolver $translationResolver,
        \App\Libraries\Cms\SlugRouter $slugRouter,
        ?\App\Libraries\Cms\TranslationSynchronizer $translationSynchronizer = null
    ) {
        parent::__construct($menuItemRepository, $responseMapper);
        $this->cacheInvalidator = $cacheInvalidator;
        $this->translationResolver = $translationResolver;
        $this->slugRouter = $slugRouter;
        $this->translationSynchronizer = $translationSynchronizer;
    }

    protected function beforeStore(array $data, ?SecurityContext $context): array
    {
        $data = parent::beforeStore($data, $context);

        $menuId = (int) $data['menu_id'];
        $parentId = isset($data['parent_id']) && $data['parent_id'] !== '' ? (int) $data['parent_id'] : null;

        $this->validateMenuExists($menuId);
        $this->validateLinkConstraints($data);
        $this->validateDuplicateLink($menuId, $parentId, $data);

        if ($parentId !== null) {
            $this->validateParentExistsAndBelongsToMenu($parentId, $menuId);
        }

        return $this->deferTranslationsFromCreate($data);
    }

    protected function afterStore(object $entity, ?SecurityContext $context): void
    {
        parent::afterStore($entity, $context);
        $this->flushDeferredTranslations(fn (array $t) => $this->saveTranslations((int) $entity->id, $t));
        $this->cacheInvalidator->invalidate(['menus']);
    }

    protected function beforeUpdate(int $id, array $data, ?SecurityContext $context): array
    {
        $data = parent::beforeUpdate($id, $data, $context);

        // Fetch current item to merge state for validation
        $currentItem = $this->repository->find($id);
        if (!$currentItem) {
            return $data;
        }

        $menuId = (int) ($data['menu_id'] ?? $currentItem->menu_id);
        $parentId = array_key_exists('parent_id', $data)
            ? ($data['parent_id'] !== '' && $data['parent_id'] !== null ? (int) $data['parent_id'] : null)
            : ($currentItem->parent_id !== null ? (int) $currentItem->parent_id : null);

        if (array_key_exists('menu_id', $data)) {
            $this->validateMenuExists($menuId);
        }

        // Validate links based on updated payload merged with existing item fields
        $mergedDataForLinks = array_merge($currentItem->toArray(), $data);
        $this->validateLinkConstraints($mergedDataForLinks);
        $this->validateDuplicateLink($menuId, $parentId, $mergedDataForLinks, $id);

        if ($parentId !== null) {
            $this->validateParentExistsAndBelongsToMenu($parentId, $menuId);
            $this->validateCircularHierarchy($id, $parentId);
        }

        return $this->deferTranslationsFromUpdate($data);
    }

    protected function afterUpdate(object $entity, ?SecurityContext $context): void
    {
        parent::afterUpdate($entity, $context);
        $this->flushDeferredTranslations(fn (array $t) => $this->saveTranslations((int) $entity->id, $t));
        $this->cacheInvalidator->invalidate(['menus']);
    }

    protected function afterDelete(object $entity, ?SecurityContext $context): void
    {
        parent::afterDelete($entity, $context);
        $this->cacheInvalidator->invalidate(['menus']);
    }

    protected function enrichEntities(array $entities): array
    {
        if (empty($entities)) {
            return $entities;
        }

        $itemIds = array_map(fn ($entity) => (int) $entity->id, $entities);

        /** @var \App\Models\MenuItemTranslationModel $translationModel */
        $translationModel = model(\App\Models\MenuItemTranslationModel::class);
        $translations = $translationModel->whereIn('menu_item_id', $itemIds)->findAll();

        $translationsGrouped = [];
        foreach ($translations as $translation) {
            if ($translation instanceof \App\Entities\MenuItemTranslationEntity) {
                $translationsGrouped[$translation->menu_item_id][] = [
                    'language_id' => (int) $translation->language_id,
                    'label'       => $translation->label,
                    'custom_url'  => $translation->custom_url,
                ];
            }
        }

        foreach ($entities as $entity) {
            $entity->translations = $translationsGrouped[$entity->id] ?? [];
        }

        return $entities;
    }

    /**
     * @param array<mixed> $translations
     */
    private function saveTranslations(int $menuItemId, array $translations): void
    {
        /** @var \App\Models\MenuItemTranslationModel $translationModel */
        $translationModel = model(\App\Models\MenuItemTranslationModel::class);

        ($this->translationSynchronizer ?? throw new \LogicException(lang('Api.translationSynchronizerRequired')))->replace(
            $translationModel,
            'menu_item_id',
            $menuItemId,
            $translations,
            static fn (array $translation): array => [
                'language_id' => (int) $translation['language_id'],
                'label'       => (string) $translation['label'],
                'custom_url'  => $translation['custom_url'] ?? null,
            ],
        );
    }

    private function validateMenuExists(int $menuId): void
    {
        $menuModel = model(\App\Models\MenuModel::class);
        if (!$menuModel->find($menuId)) {
            throw new ValidationException(
                lang('Menus.invalid_hierarchy'),
                ['menu_id' => lang('Menus.menu_not_found')]
            );
        }
    }

    private function validateParentExistsAndBelongsToMenu(int $parentId, int $menuId): void
    {
        $parent = $this->repository->find($parentId);
        if (!$parent) {
            throw new ValidationException(
                lang('Menus.invalid_hierarchy'),
                ['parent_id' => lang('Menus.parent_not_exists')]
            );
        }

        if ((int) $parent->menu_id !== $menuId) {
            throw new ValidationException(
                lang('Menus.invalid_hierarchy'),
                ['parent_id' => lang('Menus.parent_different_menu')]
            );
        }
    }

    private function validateCircularHierarchy(int $id, int $parentId): void
    {
        if ($id === $parentId) {
            throw new ValidationException(
                lang('Menus.invalid_hierarchy'),
                ['parent_id' => lang('Menus.cannot_be_own_parent')]
            );
        }

        $parent = $this->repository->find($parentId);
        $currentParentId = $parent ? $parent->parent_id : null;

        while ($currentParentId !== null) {
            if ((int) $currentParentId === $id) {
                throw new ValidationException(
                    lang('Menus.invalid_hierarchy'),
                    ['parent_id' => lang('Menus.circular_reference')]
                );
            }

            $ancestor = $this->repository->find($currentParentId);
            $currentParentId = $ancestor ? $ancestor->parent_id : null;
        }
    }

    /**
     * Resolve a MenuItem (type + IDs) to its public navigable URL.
     *
     * @param MenuItemEntity $item MenuItem to resolve
     * @param string $lang Language code (e.g., 'es', 'en')
     * @return string|null Relative URL (e.g., '/pages/slug', '/colecciones'), or null if unresolved
     */
    public function resolveLink(MenuItemEntity $item, string $lang): ?string
    {
        $translationResolver = $this->translationResolver;
        $slugRouter = $this->slugRouter;

        $customUrl = null;

        switch ($item->link_type ?? '') {
            case 'page':
                if ($item->page_id !== null) {
                    $pageSlug = $slugRouter->resolveSlug($lang, 'page', (int) $item->page_id);
                    if ($pageSlug !== null) {
                        $customUrl = $this->resolvePageUrl($pageSlug);
                    }
                }
                break;

            case 'collection_listing':
                if ($item->collection_id !== null) {
                    $prefix = $this->getCollectionPrefix((int) $item->collection_id, $lang, $translationResolver);
                    if ($prefix !== '') {
                        $customUrl = '/' . $prefix;
                    }
                }
                break;

            case 'entry':
                if ($item->entry_id !== null) {
                    $customUrl = $this->resolveEntryLink($item->entry_id, $lang, $translationResolver);
                }
                break;

            case 'custom_url':
            case 'no_link':
                // These are handled by the caller if needed
                break;
        }

        return $customUrl;
    }

    /**
     * Helper to resolve a page URL from its slug.
     *
     * @param string $pageSlug
     * @return string
     */
    private function resolvePageUrl(string $pageSlug): string
    {
        if (trim($pageSlug, '/') === 'home') {
            return '/';
        }

        return '/' . ltrim($pageSlug, '/');
    }

    /**
     * Helper to resolve an entry link.
     *
     * @param int $entryId
     * @param string $lang
     * @param \App\Libraries\Cms\TranslationResolver $translationResolver
     * @return string|null
     */
    private function resolveEntryLink(int $entryId, string $lang, \App\Libraries\Cms\TranslationResolver $translationResolver): ?string
    {
        $entryModel = model(\App\Models\EntryModel::class);
        $entry = $entryModel->find($entryId);
        if (!$entry) {
            return null;
        }

        $collectionId = is_object($entry) ? $entry->collection_id : ($entry['collection_id'] ?? null);
        if ($collectionId === null) {
            return null;
        }

        $prefix = $this->getCollectionPrefix((int) $collectionId, $lang, $translationResolver);

        // Reuse the same TranslationResolver used for the collection prefix
        // above, instead of a bespoke cms_languages + EntryTranslationModel
        // lookup — this also gives entry slugs the same default-language
        // fallback that pages (via SlugRouter) and collections already have,
        // rather than resolving only when a translation exists in $lang exactly.
        $slug = trim((string) ($translationResolver->resolve('entry', $entryId, $lang)['slug'] ?? ''));
        if ($slug === '') {
            return null;
        }

        return $prefix !== '' ? '/' . $prefix . '/' . $slug : '/' . $slug;
    }

    /**
     * Resolve the public prefix for a collection, prioritizing its published collection index page slug.
     * Fallback to the collection translation slug if not available.
     *
     * @param int $collectionId
     * @param string $lang
     * @param \App\Libraries\Cms\TranslationResolver $translationResolver
     * @return string
     */
    private function getCollectionPrefix(int $collectionId, string $lang, \App\Libraries\Cms\TranslationResolver $translationResolver): string
    {
        $pageModel = model(\App\Models\PageModel::class);
        $indexPage = $pageModel->where('collection_id', $collectionId)
            ->where('page_type', 'collection_index')
            ->where('status', 'published')
            ->where('deleted_at IS NULL')
            ->first();

        if ($indexPage !== null) {
            $pageSlug = $this->slugRouter->resolveSlug($lang, 'page', (int) $indexPage->id);
            if ($pageSlug !== null) {
                return trim($pageSlug, '/');
            }
        }

        $resolvedCollection = $translationResolver->resolve('collection', $collectionId, $lang);
        return trim((string) ($resolvedCollection['slug'] ?? ''), '/');
    }

    /**
     * @param array<string, mixed> $data
     */
    private function validateLinkConstraints(array $data): void
    {
        $linkType = $data['link_type'] ?? '';
        $pageId = isset($data['page_id']) && $data['page_id'] !== '' ? (int) $data['page_id'] : null;
        $entryId = isset($data['entry_id']) && $data['entry_id'] !== '' ? (int) $data['entry_id'] : null;
        $collectionId = isset($data['collection_id']) && $data['collection_id'] !== '' ? (int) $data['collection_id'] : null;

        $valid = false;

        switch ($linkType) {
            case 'page':
                if ($pageId !== null && $entryId === null && $collectionId === null) {
                    $valid = true;
                }
                break;
            case 'entry':
                if ($entryId !== null && $pageId === null && $collectionId === null) {
                    $valid = true;
                }
                break;
            case 'collection_listing':
                if ($collectionId !== null && $pageId === null && $entryId === null) {
                    $valid = true;
                }
                break;
            case 'custom_url':
            case 'no_link':
                if ($pageId === null && $entryId === null && $collectionId === null) {
                    $valid = true;
                }
                break;
        }

        if (!$valid) {
            throw new ValidationException(
                lang('Menus.invalid_hierarchy'),
                ['link_type' => lang('Menus.invalid_link_type')]
            );
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function validateDuplicateLink(int $menuId, ?int $parentId, array $data, ?int $ignoreId = null): void
    {
        $linkType = (string) ($data['link_type'] ?? '');

        // Marker items have no destination, so multiple root/child markers are
        // valid. A custom URL is intentionally free-form and cannot be
        // compared reliably here either.
        if (in_array($linkType, ['custom_url', 'no_link'], true)) {
            return;
        }

        $query = model(\App\Models\MenuItemModel::class)
            ->where('menu_id', $menuId)
            ->where('link_type', $linkType)
            ->where('is_active', 1);

        if ($parentId === null) {
            $query->where('parent_id IS NULL', null, false);
        } else {
            $query->where('parent_id', $parentId);
        }

        foreach (['page_id', 'entry_id', 'collection_id'] as $field) {
            if (! array_key_exists($field, $data) || $data[$field] === null || $data[$field] === '') {
                $query->where($field . ' IS NULL', null, false);

                continue;
            }

            $query->where($field, (int) $data[$field]);
        }

        if ($ignoreId !== null) {
            $query->where('id !=', $ignoreId);
        }

        $duplicate = $query->first();
        if ($duplicate !== null) {
            throw new ValidationException(
                lang('Menus.invalid_hierarchy'),
                ['menu_id' => lang('Menus.duplicate_menu_item')]
            );
        }
    }
}
