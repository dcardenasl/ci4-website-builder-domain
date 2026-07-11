<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\Entities\MenuEntity;
use App\Interfaces\Cms\MenuServiceInterface;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Exceptions\ValidationException;
use dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface;
use dcardenasl\Ci4ApiCore\Repositories\RepositoryInterface;
use dcardenasl\Ci4ApiCore\Services\BaseCrudService;

/**
 * @extends BaseCrudService<MenuEntity>
 */
class MenuService extends BaseCrudService implements MenuServiceInterface
{
    /** @var array<mixed>|null */
    private ?array $tempTranslations = null;

    private \App\Libraries\Cms\CacheInvalidationClient $cacheInvalidator;

    /**
     * @param RepositoryInterface<MenuEntity> $menuRepository
     */
    public function __construct(
        RepositoryInterface $menuRepository,
        ResponseMapperInterface $responseMapper,
        ?\App\Libraries\Cms\CacheInvalidationClient $cacheInvalidator = null
    ) {
        parent::__construct($menuRepository, $responseMapper);
        $this->cacheInvalidator = $cacheInvalidator ?? service('cacheInvalidationClient');
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

        $this->tempTranslations = $data['translations'] ?? null;
        unset($data['translations']);
        return $data;
    }

    protected function afterStore(object $entity, ?SecurityContext $context): void
    {
        parent::afterStore($entity, $context);
        if ($this->tempTranslations !== null) {
            $this->saveTranslations((int) $entity->id, $this->tempTranslations);
        }
        $this->cacheInvalidator->invalidate(['menus']);
        $this->tempTranslations = null;
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

        if (array_key_exists('translations', $data)) {
            $this->tempTranslations = $data['translations'];
            unset($data['translations']);
        } else {
            $this->tempTranslations = null;
        }

        return $data;
    }

    protected function afterUpdate(object $entity, ?SecurityContext $context): void
    {
        parent::afterUpdate($entity, $context);
        if ($this->tempTranslations !== null) {
            $this->saveTranslations((int) $entity->id, $this->tempTranslations);
        }
        $this->cacheInvalidator->invalidate(['menus']);
        $this->tempTranslations = null;
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

        $translationModel->where('menu_id', $menuId)->delete();

        foreach ($translations as $translation) {
            $translationModel->insert([
                'menu_id'     => $menuId,
                'language_id' => (int) $translation['language_id'],
                'name'        => $translation['name'],
            ]);
        }
    }
}
