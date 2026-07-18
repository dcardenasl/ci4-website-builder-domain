<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\Entities\SettingEntity;
use App\Interfaces\Cms\SettingServiceInterface;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Exceptions\ValidationException;
use dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface;
use dcardenasl\Ci4ApiCore\Repositories\RepositoryInterface;
use dcardenasl\Ci4ApiCore\Services\BaseCrudService;

/**
 * @extends BaseCrudService<SettingEntity>
 */
class SettingService extends BaseCrudService implements SettingServiceInterface
{
    /** @var array<array{language_id: int, setting_value?: string, label?: string, placeholder?: string, help_text?: string}>|null */
    private ?array $tempTranslations = null;

    private \App\Libraries\Cms\CacheInvalidationClient $cacheInvalidator;

    private \App\Libraries\Cms\FileReferenceSynchronizer $fileReferenceSynchronizer;

    /**
     * @param RepositoryInterface<SettingEntity> $settingRepository
     */
    public function __construct(
        RepositoryInterface $settingRepository,
        ResponseMapperInterface $responseMapper,
        \App\Libraries\Cms\CacheInvalidationClient $cacheInvalidator,
        \App\Libraries\Cms\FileReferenceSynchronizer $fileReferenceSynchronizer
    ) {
        parent::__construct($settingRepository, $responseMapper);
        $this->cacheInvalidator = $cacheInvalidator;
        $this->fileReferenceSynchronizer = $fileReferenceSynchronizer;
    }

    protected function beforeStore(array $data, ?SecurityContext $context): array
    {
        $data = parent::beforeStore($data, $context);
        if (array_key_exists('setting_key', $data)) {
            $existing = $this->repository->findBy('setting_key', $data['setting_key']);
            if ($existing) {
                throw new ValidationException(
                    lang('Api.validationFailed'),
                    ['setting_key' => lang('Settings.key_must_be_unique')]
                );
            }
        }

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
        $this->fileReferenceSynchronizer->syncSetting((int) $entity->id);
        $this->cacheInvalidator->invalidate(['settings']);
        $this->tempTranslations = null;
    }

    protected function beforeUpdate(int $id, array $data, ?SecurityContext $context): array
    {
        $data = parent::beforeUpdate($id, $data, $context);
        if (array_key_exists('setting_key', $data)) {
            $existing = $this->repository->findBy('setting_key', $data['setting_key']);
            if ($existing && (int) $existing->id !== $id) {
                throw new ValidationException(
                    lang('Api.validationFailed'),
                    ['setting_key' => lang('Settings.key_must_be_unique')]
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
        if ($this->tempTranslations !== null && $entity->is_translatable) {
            $this->saveTranslations((int) $entity->id, $this->tempTranslations);
        }
        $this->fileReferenceSynchronizer->syncSetting((int) $entity->id);
        $this->cacheInvalidator->invalidate(['settings']);
        $this->tempTranslations = null;
    }

    protected function afterDelete(object $entity, ?SecurityContext $context): void
    {
        parent::afterDelete($entity, $context);
        $this->fileReferenceSynchronizer->removeResourceReferences('setting', (int) $entity->id);
        $this->cacheInvalidator->invalidate(['settings']);
    }

    protected function enrichEntities(array $entities): array
    {
        if (empty($entities)) {
            return $entities;
        }

        $settingIds = array_map(fn ($entity) => (int) $entity->id, $entities);

        /** @var \App\Models\SettingTranslationModel $translationModel */
        $translationModel = model(\App\Models\SettingTranslationModel::class);
        $translations = $translationModel->whereIn('setting_id', $settingIds)->findAll();

        $translationsGrouped = [];
        foreach ($translations as $translation) {
            /** @var \App\Entities\SettingTranslationEntity $translation */
            $entry = ['language_id' => (int) $translation->language_id];

            if ($translation->setting_value !== null) {
                $entry['setting_value'] = $translation->setting_value;
            }
            if ($translation->label !== null && $translation->label !== '') {
                $entry['label'] = $translation->label;
            }
            if ($translation->placeholder !== null && $translation->placeholder !== '') {
                $entry['placeholder'] = $translation->placeholder;
            }
            if ($translation->help_text !== null && $translation->help_text !== '') {
                $entry['help_text'] = $translation->help_text;
            }

            $translationsGrouped[$translation->setting_id][] = $entry;
        }

        foreach ($entities as $entity) {
            $entity->translations = $translationsGrouped[$entity->id] ?? [];
        }

        return $entities;
    }

    /**
     * @param array<array{language_id: int, setting_value?: string, label?: string, placeholder?: string, help_text?: string}> $translations
     */
    private function saveTranslations(int $settingId, array $translations): void
    {
        /** @var \App\Models\SettingTranslationModel $translationModel */
        $translationModel = model(\App\Models\SettingTranslationModel::class);

        $translationModel->where('setting_id', $settingId)->delete();

        foreach ($translations as $translation) {
            $langId = (int) $translation['language_id'];
            $row    = [
                'setting_id'    => $settingId,
                'language_id'   => $langId,
                'setting_value' => $translation['setting_value'] ?? null,
                'label'         => $translation['label'] ?? null,
                'placeholder'   => $translation['placeholder'] ?? null,
                'help_text'     => $translation['help_text'] ?? null,
            ];

            $result = $translationModel->insert($row);

            if ($result === false) {
                $errors = $translationModel->errors();
                throw new ValidationException(
                    lang('Api.validationFailed'),
                    $errors ?: ['translations' => lang('Api.invalidTranslation')]
                );
            }
        }
    }
}
