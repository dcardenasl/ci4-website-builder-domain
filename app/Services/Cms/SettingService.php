<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\Entities\SettingEntity;
use App\Interfaces\Cms\SettingServiceInterface;
use App\Libraries\Cms\FileUrlResolver;
use App\Libraries\Cms\PublicLocaleResolver;
use App\Libraries\Cms\TranslationResolver;
use App\Traits\Services\HasDeferredTranslations;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Exceptions\ValidationException;
use dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface;
use dcardenasl\Ci4ApiCore\Repositories\RepositoryInterface;
use dcardenasl\Ci4ApiCore\Services\BaseCrudService;
use dcardenasl\Ci4ApiCore\Support\RequestDtoFactory;

/**
 * @extends BaseCrudService<SettingEntity>
 */
class SettingService extends BaseCrudService implements SettingServiceInterface
{
    use HasDeferredTranslations;

    /** Prevent per-item network side effects during an atomic batch. */
    private bool $batchMode = false;

    private \App\Libraries\Cms\CacheInvalidationClient $cacheInvalidator;

    private \App\Libraries\Cms\FileReferenceSynchronizer $fileReferenceSynchronizer;

    private ?\App\Libraries\Cms\TranslationSynchronizer $translationSynchronizer;

    /**
     * @param RepositoryInterface<SettingEntity> $settingRepository
     */
    public function __construct(
        RepositoryInterface $settingRepository,
        ResponseMapperInterface $responseMapper,
        \App\Libraries\Cms\CacheInvalidationClient $cacheInvalidator,
        \App\Libraries\Cms\FileReferenceSynchronizer $fileReferenceSynchronizer,
        private readonly TranslationResolver $translationResolver,
        private readonly FileUrlResolver $fileUrlResolver,
        private readonly PublicLocaleResolver $publicLocaleResolver,
        private readonly RequestDtoFactory $requestDtoFactory,
        ?\App\Libraries\Cms\TranslationSynchronizer $translationSynchronizer = null
    ) {
        parent::__construct($settingRepository, $responseMapper);
        $this->cacheInvalidator = $cacheInvalidator;
        $this->fileReferenceSynchronizer = $fileReferenceSynchronizer;
        $this->translationSynchronizer = $translationSynchronizer;
    }

    /**
     * List public+active settings keyed by setting_key, with translation and
     * file_id resolution applied.
     *
     * @return array<string, mixed>
     */
    public function listPublic(?string $acceptLanguageHeader): array
    {
        $lang = $this->publicLocaleResolver->resolve($acceptLanguageHeader);

        /** @var list<SettingEntity> $settings */
        $settings = $this->repository->getModel()
            ->where('is_public', 1)
            ->where('is_active', 1)
            ->orderBy('sort_order', 'ASC')
            ->findAll();

        $result = [];
        foreach ($settings as $setting) {
            if ($setting->is_translatable) {
                $translation = $this->translationResolver->resolve('setting', (int) $setting->id, $lang);
                $value = $translation['setting_value'] ?? $setting->setting_value;
            } else {
                $value = $setting->setting_value;
            }

            if ($setting->setting_type === 'file_id') {
                $meta = is_array($setting->setting_meta) ? $setting->setting_meta : [];
                $resolvedUrl = $this->fileUrlResolver->resolve((int) ($setting->setting_value ?? 0), 'original');
                $value = [
                    'file_id'   => (int) ($setting->setting_value ?? 0),
                    'url'       => $resolvedUrl ?? ($meta['url'] ?? null),
                    'mime_type' => $meta['mime_type'] ?? null,
                ];
            }

            $result[$setting->setting_key] = $value;
        }

        return $result;
    }

    /** @param list<array{id: int, payload: array<string, mixed>}> $updates */
    public function batchUpdate(array $updates, ?SecurityContext $context = null): array
    {
        $this->batchMode = true;
        try {
            $updated = $this->wrapInTransaction(function () use ($updates, $context): array {
                $updated = [];
                foreach ($updates as $update) {
                    $dto = $this->requestDtoFactory->make(
                        \App\DTO\Request\Cms\SettingUpdateRequestDTO::class,
                        $update['payload']
                    );
                    $this->update((int) $update['id'], $dto, $context);
                    $updated[] = (int) $update['id'];
                }

                return $updated;
            });
        } finally {
            $this->batchMode = false;
        }

        // One best-effort notification after commit. Calling the web app from
        // every item made a large batch spend the whole PHP execution budget
        // in network calls and could deadlock when the web app was busy.
        $this->cacheInvalidator->invalidate(['settings']);

        return ['updated' => $updated];
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

        return $this->deferTranslationsFromCreate($data);
    }

    protected function afterStore(object $entity, ?SecurityContext $context): void
    {
        parent::afterStore($entity, $context);
        $this->flushDeferredTranslations(
            fn (array $t) => $this->saveTranslations((int) $entity->id, $t),
            (bool) $entity->is_translatable
        );
        $this->fileReferenceSynchronizer->syncSetting((int) $entity->id);
        if (!$this->batchMode) {
            $this->cacheInvalidator->invalidate(['settings']);
        }
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

        return $this->deferTranslationsFromUpdate($data);
    }

    protected function afterUpdate(object $entity, ?SecurityContext $context): void
    {
        parent::afterUpdate($entity, $context);
        $this->flushDeferredTranslations(
            fn (array $t) => $this->saveTranslations((int) $entity->id, $t),
            (bool) $entity->is_translatable
        );
        $this->fileReferenceSynchronizer->syncSetting((int) $entity->id);
        if (!$this->batchMode) {
            $this->cacheInvalidator->invalidate(['settings']);
        }
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
     * @param array<mixed> $translations
     */
    private function saveTranslations(int $settingId, array $translations): void
    {
        /** @var \App\Models\SettingTranslationModel $translationModel */
        $translationModel = model(\App\Models\SettingTranslationModel::class);

        ($this->translationSynchronizer ?? throw new \LogicException(lang('Api.translationSynchronizerRequired')))->replace(
            $translationModel,
            'setting_id',
            $settingId,
            $translations,
            static fn (array $translation): array => [
                'language_id'   => (int) $translation['language_id'],
                'setting_value' => $translation['setting_value'] ?? null,
                'label'         => $translation['label'] ?? null,
                'placeholder'   => $translation['placeholder'] ?? null,
                'help_text'     => $translation['help_text'] ?? null,
            ],
        );
    }
}
