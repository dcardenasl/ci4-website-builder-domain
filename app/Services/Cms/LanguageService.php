<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\Entities\LanguageEntity;
use App\Interfaces\Cms\LanguageServiceInterface;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Exceptions\BadRequestException;
use dcardenasl\Ci4ApiCore\Exceptions\ValidationException;
use dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface;
use dcardenasl\Ci4ApiCore\Repositories\RepositoryInterface;
use dcardenasl\Ci4ApiCore\Services\BaseCrudService;

/**
 * @extends BaseCrudService<LanguageEntity>
 */
class LanguageService extends BaseCrudService implements LanguageServiceInterface
{
    /**
     * @param RepositoryInterface<LanguageEntity> $languageRepository
     */
    public function __construct(
        RepositoryInterface $languageRepository,
        ResponseMapperInterface $responseMapper
    ) {
        parent::__construct($languageRepository, $responseMapper);
    }

    protected function beforeStore(array $data, ?SecurityContext $context): array
    {
        $data = parent::beforeStore($data, $context);

        $existing = $this->repository->findBy('code', $data['code']);
        if ($existing) {
            throw new ValidationException(
                lang('Api.validationFailed'),
                ['code' => lang('Cms.languages.code_already_taken', [$data['code']])]
            );
        }

        // If this is the first language being created, force is_default = true
        /** @var \App\Models\LanguageModel $model */
        $model = model(\App\Models\LanguageModel::class);
        $count = $model->countAllResults();
        if ($count === 0) {
            $data['is_default'] = true;
            $data['is_active'] = true;
        }

        if (isset($data['is_default']) && filter_var($data['is_default'], FILTER_VALIDATE_BOOLEAN)) {
            // Seteamos todos los demás en is_default = false
            $this->resetDefaults();
        }

        return $data;
    }

    protected function beforeUpdate(int $id, array $data, ?SecurityContext $context): array
    {
        $data = parent::beforeUpdate($id, $data, $context);

        if (array_key_exists('code', $data)) {
            $existing = $this->repository->findBy('code', $data['code']);
            if ($existing && (int) $existing->id !== $id) {
                throw new ValidationException(
                    lang('Api.validationFailed'),
                    ['code' => lang('Cms.languages.code_already_taken', [$data['code']])]
                );
            }
        }

        /** @var LanguageEntity|null $entity */
        $entity = $this->repository->find($id);
        if (!$entity) {
            return $data;
        }

        // Deactivation check
        if (isset($data['is_active']) && !filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN)) {
            $isDefault = $data['is_default'] ?? $entity->is_default;
            if ($isDefault) {
                throw new BadRequestException(lang('Cms.languages.cannot_deactivate_default'));
            }
        }

        // Default language modification check
        if (isset($data['is_default'])) {
            $newIsDefault = filter_var($data['is_default'], FILTER_VALIDATE_BOOLEAN);
            if ($newIsDefault) {
                $this->resetDefaults($id);
            } elseif ($entity->is_default) {
                // Cannot unset the default language without setting another one first
                throw new BadRequestException(lang('Cms.languages.must_have_default'));
            }
        }

        return $data;
    }

    protected function beforeDelete(int $id, ?SecurityContext $context): void
    {
        parent::beforeDelete($id, $context);

        /** @var LanguageEntity|null $entity */
        $entity = $this->repository->find($id);
        if ($entity && $entity->is_default) {
            throw new BadRequestException(lang('Cms.languages.cannot_delete_default'));
        }
    }

    /**
     * @return list<array{code: string, name: string, native_name: string, is_default: bool}>
     */
    public function listPublic(): array
    {
        /** @var list<LanguageEntity> $languages */
        $languages = $this->repository->getModel()
            ->where('is_active', 1)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('code', 'ASC')
            ->findAll();

        return array_map(static fn (LanguageEntity $language): array => [
            'code' => (string) $language->code,
            'name' => (string) $language->name,
            'native_name' => (string) $language->native_name,
            'is_default' => (bool) $language->is_default,
        ], $languages);
    }

    private function resetDefaults(?int $exceptId = null): void
    {
        /** @var \App\Models\LanguageModel $model */
        $model = model(\App\Models\LanguageModel::class);
        $builder = $model->builder();
        if ($exceptId !== null) {
            $builder->where('id !=', $exceptId);
        }
        $builder->update(['is_default' => 0]);
    }
}
