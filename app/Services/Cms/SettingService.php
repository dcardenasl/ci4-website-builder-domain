<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\Entities\SettingEntity;
use App\Interfaces\Cms\SettingServiceInterface;
use App\Models\SettingTranslationModel;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface;
use dcardenasl\Ci4ApiCore\Repositories\RepositoryInterface;
use dcardenasl\Ci4ApiCore\Services\BaseCrudService;

/**
 * @extends BaseCrudService<SettingEntity>
 */
class SettingService extends BaseCrudService implements SettingServiceInterface
{
    /** @var array<array{language_id: int, setting_value: string}>|null */
    private ?array $tempTranslations = null;

    /**
     * @param RepositoryInterface<SettingEntity> $settingRepository
     */
    public function __construct(
        RepositoryInterface $settingRepository,
        ResponseMapperInterface $responseMapper
    ) {
        parent::__construct($settingRepository, $responseMapper);
    }

    protected function beforeStore(array $data, ?SecurityContext $context): array
    {
        $data = parent::beforeStore($data, $context);
        $this->tempTranslations = $data['translations'] ?? null;
        unset($data['translations']);
        return $data;
    }

    protected function afterStore(object $entity, ?SecurityContext $context): void
    {
        parent::afterStore($entity, $context);
        if ($this->tempTranslations !== null && $entity->is_translatable) {
            $this->saveTranslations((int) $entity->id, $this->tempTranslations);
        }
        $this->tempTranslations = null;
    }

    protected function beforeUpdate(int $id, array $data, ?SecurityContext $context): array
    {
        $data = parent::beforeUpdate($id, $data, $context);
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
        if ($this->tempTranslations !== null && $entity->is_translatable) {
            $this->saveTranslations((int) $entity->id, $this->tempTranslations);
        }
        $this->tempTranslations = null;
    }

    protected function enrichEntities(array $entities): array
    {
        if (empty($entities)) {
            return $entities;
        }

        $settingIds = array_map(fn ($entity) => (int) $entity->id, $entities);

        /** @var SettingTranslationModel $translationModel */
        $translationModel = model(SettingTranslationModel::class);
        $translations = $translationModel->whereIn('setting_id', $settingIds)->findAll();

        $translationsGrouped = [];
        foreach ($translations as $translation) {
            /** @var \App\Entities\SettingTranslationEntity $translation */
            $translationsGrouped[$translation->setting_id][] = [
                'language_id'   => (int) $translation->language_id,
                'setting_value' => $translation->setting_value,
            ];
        }

        foreach ($entities as $entity) {
            $entity->translations = $translationsGrouped[$entity->id] ?? [];
        }

        return $entities;
    }

    /**
     * @param array<array{language_id: int, setting_value: string}> $translations
     */
    private function saveTranslations(int $settingId, array $translations): void
    {
        /** @var SettingTranslationModel $translationModel */
        $translationModel = model(SettingTranslationModel::class);

        // Clear existing translations for this setting
        $translationModel->where('setting_id', $settingId)->delete();

        foreach ($translations as $translation) {
            $translationModel->insert([
                'setting_id'    => $settingId,
                'language_id'   => (int) $translation['language_id'],
                'setting_value' => $translation['setting_value'],
            ]);
        }
    }
}
