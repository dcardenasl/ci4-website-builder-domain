<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\Entities\MenuEntity;
use App\Entities\MenuItemEntity;
use App\Interfaces\Cms\MenuItemServiceInterface;
use App\Interfaces\Cms\MenuServiceInterface;
use App\Libraries\Cms\TranslationResolver;
use App\Traits\Services\HasDeferredTranslations;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Exceptions\NotFoundException;
use dcardenasl\Ci4ApiCore\Exceptions\ValidationException;
use dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface;
use dcardenasl\Ci4ApiCore\Repositories\RepositoryInterface;
use dcardenasl\Ci4ApiCore\Services\BaseCrudService;

/**
 * @extends BaseCrudService<MenuEntity>
 */
class MenuService extends BaseCrudService implements MenuServiceInterface
{
    use HasDeferredTranslations;

    private \App\Libraries\Cms\CacheInvalidationClient $cacheInvalidator;

    private ?\App\Libraries\Cms\TranslationSynchronizer $translationSynchronizer;

    /**
     * @param RepositoryInterface<MenuEntity> $menuRepository
     * @param RepositoryInterface<MenuItemEntity> $menuItemRepository
     */
    public function __construct(
        RepositoryInterface $menuRepository,
        ResponseMapperInterface $responseMapper,
        \App\Libraries\Cms\CacheInvalidationClient $cacheInvalidator,
        private readonly RepositoryInterface $menuItemRepository,
        private readonly TranslationResolver $translationResolver,
        private readonly MenuItemServiceInterface $menuItemService,
        ?\App\Libraries\Cms\TranslationSynchronizer $translationSynchronizer = null
    ) {
        parent::__construct($menuRepository, $responseMapper);
        $this->cacheInvalidator = $cacheInvalidator;
        $this->translationSynchronizer = $translationSynchronizer;
    }

    /**
     * Resolve a public menu tree by its menu_key and language.
     *
     * @return array<string, mixed>
     */
    public function showPublic(string $menuKey, string $lang): array
    {
        /** @var MenuEntity|null $menu */
        $menu = $this->repository->getModel()
            ->where('menu_key', $menuKey)
            ->where('is_active', 1)
            ->first();

        if ($menu === null) {
            throw new NotFoundException(lang('Menus.not_found'));
        }

        $menuTranslation = $this->translationResolver->resolve('menu', (int) $menu->id, $lang);

        /** @var list<MenuItemEntity> $items */
        $items = $this->menuItemRepository->getModel()
            ->where('menu_id', $menu->id)
            ->where('is_active', 1)
            ->orderBy('sort_order', 'ASC')
            ->findAll();

        $flatList = [];
        foreach ($items as $item) {
            if ($item instanceof MenuItemEntity) {
                $resolved = $this->translationResolver->resolve('menu_item', (int) $item->id, $lang);
                $customUrl = $this->menuItemService->resolveLink($item, $lang);

                if ($customUrl === null && $item->link_type === 'custom_url') {
                    $customUrl = $resolved['custom_url'] ?? null;
                }

                $flatList[] = array_merge($item->toArray(), [
                    'label'       => $resolved['label'] ?? '',
                    'custom_url'  => $customUrl,
                    'is_fallback' => $resolved['is_fallback'] ?? false,
                ]);
            }
        }

        return [
            'menu_key' => $menu->menu_key,
            'location' => $menu->location,
            'name'     => $menuTranslation['name'] ?? $menu->menu_key,
            'items'    => $this->buildTree($flatList, null),
        ];
    }

    /**
     * Reconstruct hierarchical tree from flat list of menu items.
     *
     * @param array<array<string, mixed>> $items
     * @return array<array<string, mixed>>
     */
    private function buildTree(array &$items, ?int $parentId): array
    {
        $branch = [];

        foreach ($items as $item) {
            $itemParentId = $item['parent_id'] !== null ? (int) $item['parent_id'] : null;

            if ($itemParentId === $parentId) {
                $children = $this->buildTree($items, (int) $item['id']);
                $item['children'] = $children;
                $branch[] = $item;
            }
        }

        return $branch;
    }

    protected function beforeStore(array $data, ?SecurityContext $context): array
    {
        $data = parent::beforeStore($data, $context);

        // Validate menu_key uniqueness
        $existing = $this->repository->findBy('menu_key', $data['menu_key']);
        if ($existing) {
            throw new ValidationException(
                lang('Menus.menu_key_must_be_unique'),
                ['menu_key' => lang('Menus.menu_key_already_taken', [$data['menu_key']])]
            );
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

        if (array_key_exists('menu_key', $data)) {
            $existing = $this->repository->findBy('menu_key', $data['menu_key']);
            if ($existing && (int) $existing->id !== $id) {
                throw new ValidationException(
                    lang('Menus.menu_key_must_be_unique'),
                    ['menu_key' => lang('Menus.menu_key_already_taken', [$data['menu_key']])]
                );
            }
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

        $menuIds = array_map(fn ($entity) => (int) $entity->id, $entities);

        /** @var \App\Models\MenuTranslationModel $translationModel */
        $translationModel = model(\App\Models\MenuTranslationModel::class);
        $translations = $translationModel->whereIn('menu_id', $menuIds)->findAll();

        $translationsGrouped = [];
        foreach ($translations as $translation) {
            if ($translation instanceof \App\Entities\MenuTranslationEntity) {
                $translationsGrouped[$translation->menu_id][] = [
                    'language_id' => (int) $translation->language_id,
                    'name'        => $translation->name,
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
    private function saveTranslations(int $menuId, array $translations): void
    {
        /** @var \App\Models\MenuTranslationModel $translationModel */
        $translationModel = model(\App\Models\MenuTranslationModel::class);

        ($this->translationSynchronizer ?? throw new \LogicException(lang('Api.translationSynchronizerRequired')))->replace(
            $translationModel,
            'menu_id',
            $menuId,
            $translations,
            static fn (array $translation): array => [
                'language_id' => (int) $translation['language_id'],
                'name'        => (string) $translation['name'],
            ],
        );
    }
}
