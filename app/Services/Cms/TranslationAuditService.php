<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\Interfaces\Cms\TranslationAuditServiceInterface;
use App\Libraries\Cms\TranslationAuditSupport;
use App\Libraries\Cms\TranslationResourceCatalog;
use dcardenasl\Ci4ApiCore\Repositories\RepositoryInterface;

/**
 * Audits translation completeness across every translatable CMS resource.
 *
 * Nine of the eleven audited resource types (all but `setting` and
 * `block_instance`, which have genuinely different rules — a default-language
 * fallback and a schema-driven field set, respectively) share the exact same
 * shape: find all rows of a resource, group its translation rows by resource
 * id, and compare against `TranslationResourceCatalog`. `buildSimpleResourceDescriptors()`
 * is the single catalog of that per-resource wiring (repository, translation
 * repository, foreign key, human-readable reference, optional extra context)
 * that `getMissingTranslationsReport()`, `auditResource()` and
 * `countAuditableResources()` all read from, instead of nine near-identical
 * private methods and a nine-case switch (H-011,
 * docs/audits/2026-07-10-auditoria-profunda-robustez.md).
 *
 * Every collaborator is constructor-injected through `RepositoryInterface`
 * (the same Repository/DI seam BlockTypeService and friends use — ADR-0002,
 * ADR-005), not a concrete `App\Models\*` class: this class used to `model()`
 * 20 Models directly inside its own constructor, which is exactly the
 * coupling those ADRs forbid. Wiring lives in
 * Config\CmsDomainServices::translationAuditService(). Query needs beyond
 * `find()`/`findAll()` (a `where()` filter, a join) go through
 * `RepositoryInterface::getModel()` — the same escape hatch BlockTypeService
 * uses for its own raw queries — rather than re-introducing a Model
 * dependency.
 */
class TranslationAuditService implements TranslationAuditServiceInterface
{
    /**
     * @var list<array{
     *   type: string,
     *   repository: RepositoryInterface<object>,
     *   translationRepository: RepositoryInterface<object>,
     *   fk: string,
     *   reference: callable(array<string, mixed>, array<int, array<string, mixed>>): string,
     *   extra: null|callable(array<string, mixed>): array<string, mixed>,
     *   fetch: callable(): list<mixed>,
     *   count: callable(): int,
     * }>
     */
    private array $simpleResources;

    /**
     * @param RepositoryInterface<object> $languageRepository
     * @param RepositoryInterface<object> $pageRepository
     * @param RepositoryInterface<object> $pageTranslationRepository
     * @param RepositoryInterface<object> $menuRepository
     * @param RepositoryInterface<object> $menuTranslationRepository
     * @param RepositoryInterface<object> $menuItemRepository
     * @param RepositoryInterface<object> $menuItemTranslationRepository
     * @param RepositoryInterface<object> $settingRepository
     * @param RepositoryInterface<object> $settingTranslationRepository
     * @param RepositoryInterface<object> $collectionRepository
     * @param RepositoryInterface<object> $collectionTranslationRepository
     * @param RepositoryInterface<object> $categoryRepository
     * @param RepositoryInterface<object> $categoryTranslationRepository
     * @param RepositoryInterface<object> $tagRepository
     * @param RepositoryInterface<object> $tagTranslationRepository
     * @param RepositoryInterface<object> $entryRepository
     * @param RepositoryInterface<object> $entryTranslationRepository
     * @param RepositoryInterface<object> $formRepository
     * @param RepositoryInterface<object> $formTranslationRepository
     * @param RepositoryInterface<object> $formFieldRepository
     * @param RepositoryInterface<object> $formFieldTranslationRepository
     */
    public function __construct(
        private readonly RepositoryInterface $languageRepository,
        private readonly RepositoryInterface $pageRepository,
        private readonly RepositoryInterface $pageTranslationRepository,
        private readonly RepositoryInterface $menuRepository,
        private readonly RepositoryInterface $menuTranslationRepository,
        private readonly RepositoryInterface $menuItemRepository,
        private readonly RepositoryInterface $menuItemTranslationRepository,
        private readonly RepositoryInterface $settingRepository,
        private readonly RepositoryInterface $settingTranslationRepository,
        private readonly RepositoryInterface $collectionRepository,
        private readonly RepositoryInterface $collectionTranslationRepository,
        private readonly RepositoryInterface $categoryRepository,
        private readonly RepositoryInterface $categoryTranslationRepository,
        private readonly RepositoryInterface $tagRepository,
        private readonly RepositoryInterface $tagTranslationRepository,
        private readonly RepositoryInterface $entryRepository,
        private readonly RepositoryInterface $entryTranslationRepository,
        private readonly RepositoryInterface $formRepository,
        private readonly RepositoryInterface $formTranslationRepository,
        private readonly RepositoryInterface $formFieldRepository,
        private readonly RepositoryInterface $formFieldTranslationRepository,
        private readonly TranslationAuditSupport $support,
        private readonly BlockInstanceTranslationAuditor $blockAuditor,
    ) {
        $this->simpleResources = $this->buildSimpleResourceDescriptors();
    }

    /**
     * {@inheritdoc}
     *
     * @return array<int, array<string, mixed>>
     */
    public function getOverallCompleteness(): array
    {
        $activeLanguages = $this->getActiveLanguages();
        if ($activeLanguages === []) {
            return [];
        }

        $totalElements = $this->countAuditableResources();
        $missingCounts = [];
        foreach ($this->getMissingTranslationsReport() as $issue) {
            $languageId = (int) ($issue['language_id'] ?? 0);
            if ($languageId > 0) {
                $missingCounts[$languageId] = ($missingCounts[$languageId] ?? 0) + 1;
            }
        }

        $report = [];
        foreach ($activeLanguages as $lang) {
            $langId = (int) $lang->id;
            $completedElements = max(0, $totalElements - ($missingCounts[$langId] ?? 0));
            $percentage = $totalElements > 0 ? round(($completedElements / $totalElements) * 100) : 100;

            $report[] = [
                'language_id' => $langId,
                'code' => $lang->code,
                'name' => $lang->native_name ?? $lang->name,
                'is_default' => (bool) $lang->is_default,
                'total_elements' => $totalElements,
                'completed_elements' => $completedElements,
                'percentage' => (int) $percentage,
            ];
        }

        return $report;
    }

    /**
     * {@inheritdoc}
     */
    public function getMissingTranslationsReport(array $filters = []): array
    {
        $activeLanguages = $this->getActiveLanguages();
        if ($activeLanguages === []) {
            return [];
        }

        $issues = [];
        foreach ($this->simpleResources as $descriptor) {
            $issues = array_merge($issues, $this->auditSimpleResources($descriptor, $activeLanguages, $filters));
        }
        $issues = array_merge($issues, $this->auditSettingTranslations($activeLanguages, $filters));
        $issues = array_merge($issues, $this->blockAuditor->audit($activeLanguages, $filters));

        return array_values(array_filter($issues, function (array $issue) use ($filters): bool {
            $resource = (string) ($filters['resource'] ?? '');
            $status = (string) ($filters['status'] ?? '');
            $search = mb_strtolower((string) ($filters['search'] ?? ''));

            if ($resource !== '' && (string) ($issue['resource'] ?? '') !== $resource) {
                return false;
            }
            if ($status !== '' && (string) ($issue['status'] ?? '') !== $status) {
                return false;
            }
            if ($search !== '') {
                $haystack = mb_strtolower(implode(' ', [
                    (string) ($issue['reference_name'] ?? ''),
                    (string) ($issue['resource'] ?? ''),
                    (string) ($issue['language_code'] ?? ''),
                    (string) ($issue['detail'] ?? ''),
                ]));
                if (! str_contains($haystack, $search)) {
                    return false;
                }
            }

            return true;
        }));
    }

    /**
     * {@inheritdoc}
     */
    public function auditResource(string $resourceType, int $resourceId): array
    {
        $activeLanguages = $this->getActiveLanguages();
        $fieldDefinitions = [];
        $translations = [];
        $valueResolver = static function (array $row, string $fieldKey, array $fieldDefinition): mixed {
            return $row[$fieldKey] ?? null;
        };
        $defaultLanguageId = null;
        $resourceRow = [];

        $descriptor = $this->findSimpleResourceDescriptor($resourceType);

        if ($descriptor !== null) {
            $resource = $descriptor['repository']->find($resourceId);
            if ($resource === null) {
                return [];
            }

            $translations = $this->support->groupTranslationsByResource(
                $descriptor['translationRepository']->getModel()->where($descriptor['fk'], $resourceId)->findAll(),
                $descriptor['fk']
            )[$resourceId] ?? [];
            $fieldDefinitions = TranslationResourceCatalog::fields($resourceType);
            $resourceRow = $this->support->toArray($resource);
        } elseif ($resourceType === 'setting') {
            $resource = $this->settingRepository->getModel()->where('is_translatable', 1)->find($resourceId);
            if ($resource === null) {
                return [];
            }

            $translations = $this->support->groupTranslationsByResource(
                $this->settingTranslationRepository->getModel()->where('setting_id', $resourceId)->findAll(),
                'setting_id'
            )[$resourceId] ?? [];
            $fieldDefinitions = TranslationResourceCatalog::fields('setting');
            $defaultLanguageId = $this->getDefaultLanguageId();
            $resourceRow = $this->support->toArray($resource);
            if ($defaultLanguageId !== null) {
                $translations[$defaultLanguageId] = ['setting_value' => $resourceRow['setting_value'] ?? null];
            }
        } elseif ($resourceType === 'block_instance') {
            $resolved = $this->blockAuditor->resolveForResource($resourceId);
            if ($resolved === null) {
                return [];
            }

            [$resource, $fieldDefinitions, $translations, $valueResolver] = $resolved;
            $resourceRow = $this->support->toArray($resource);
        } else {
            return [];
        }

        $report = [];
        foreach ($activeLanguages as $lang) {
            $langId = (int) $lang->id;
            if ($resourceType === 'setting' && $defaultLanguageId === $langId) {
                $translation = ['setting_value' => $resourceRow['setting_value'] ?? null];
            } else {
                $translation = $translations[$langId] ?? null;
            }
            $isSettingDefaultLanguage = $resourceType === 'setting' && $defaultLanguageId === $langId;
            [$status, $detail] = $this->support->evaluateTranslationState(
                $translation,
                $translations,
                $fieldDefinitions,
                $langId,
                $valueResolver,
                $isSettingDefaultLanguage ? null : (isset($resourceRow['updated_at']) ? (string) $resourceRow['updated_at'] : null)
            );

            // The admin's block-editor tab dots consume this same endpoint
            // (auditResource('block_instance', $id)) and use the same
            // 4-state vocabulary as auditOwnerBlocks() — see
            // TranslationAuditSupport::collapseForBlockBadge() for why.
            // Every other resource type (page, entry, setting, ...) keeps
            // the full vocabulary verbatim here, unaffected.
            if ($resourceType === 'block_instance') {
                $status = $this->support->collapseForBlockBadge($status);
            }

            $report[$lang->code] = [
                'language_id' => $langId,
                'status' => $status,
                'detail' => $detail,
            ];
        }

        return $report;
    }

    /**
     * {@inheritdoc}
     */
    public function auditOwnerBlocks(string $ownerType, int $ownerId): array
    {
        $activeLanguages = $this->getActiveLanguages();
        if ($activeLanguages === []) {
            return ['blocks' => [], 'summary' => []];
        }

        return $this->blockAuditor->auditForOwner($ownerType, $ownerId, $activeLanguages);
    }

    /**
     * @return list<\App\Entities\LanguageEntity>
     */
    private function getActiveLanguages(): array
    {
        $rows = $this->languageRepository->getModel()->where('is_active', 1)->findAll();

        return array_values(array_filter(
            $rows,
            static fn ($row): bool => $row instanceof \App\Entities\LanguageEntity
        ));
    }

    /**
     * The nine resource types that share the exact same audit shape: find
     * all rows, group translations by foreign key, compare against
     * TranslationResourceCatalog. `setting` (default-language fallback) and
     * `block_instance` (schema-driven fields) stay outside this catalog
     * because their rules genuinely differ, not because of oversight.
     *
     * @return list<array{
     *   type: string,
     *   repository: RepositoryInterface<object>,
     *   translationRepository: RepositoryInterface<object>,
     *   fk: string,
     *   reference: callable(array<string, mixed>, array<int, array<string, mixed>>): string,
     *   extra: null|callable(array<string, mixed>): array<string, mixed>,
     *   fetch: callable(): list<mixed>,
     *   count: callable(): int,
     * }>
     */
    private function buildSimpleResourceDescriptors(): array
    {
        $reference = static function (array $row, string $fallback, array $translations = []): string {
            foreach (['title', 'name', 'label', 'setting_key', 'form_key', 'field_key', 'collection_key', 'menu_key', 'slug'] as $field) {
                $value = trim((string) ($row[$field] ?? ''));
                if ($value !== '') {
                    return $value;
                }
            }

            // Pages, Menus, Menu Items, Collections and Forms have no
            // canonical title/name column on their own table at all — their
            // content lives purely in the translation rows (confirmed
            // against their response DTOs), so the loop above always misses
            // and would otherwise fall back to a technical "Page #12"
            // placeholder even when a real title exists in any language.
            foreach ($translations as $translation) {
                $translationRow = is_array($translation) ? $translation : (array) $translation;
                foreach (['title', 'name', 'label'] as $field) {
                    $value = trim((string) ($translationRow[$field] ?? ''));
                    if ($value !== '') {
                        return $value;
                    }
                }
            }

            return $fallback;
        };

        return [
            [
                'type' => 'page',
                'repository' => $this->pageRepository,
                'translationRepository' => $this->pageTranslationRepository,
                'fk' => 'page_id',
                'reference' => static fn (array $r, array $t = []): string => $reference($r, 'Page #' . (int) ($r['id'] ?? 0), $t),
                'extra' => null,
                'fetch' => fn (): array => $this->pageRepository->getModel()->findAll(),
                'count' => fn (): int => (int) $this->pageRepository->getModel()->countAllResults(),
            ],
            [
                'type' => 'menu',
                'repository' => $this->menuRepository,
                'translationRepository' => $this->menuTranslationRepository,
                'fk' => 'menu_id',
                'reference' => static fn (array $r, array $t = []): string => $reference($r, 'Menu #' . (int) ($r['id'] ?? 0), $t),
                'extra' => null,
                'fetch' => fn (): array => $this->menuRepository->getModel()->findAll(),
                'count' => fn (): int => (int) $this->menuRepository->getModel()->countAllResults(),
            ],
            [
                'type' => 'menu_item',
                'repository' => $this->menuItemRepository,
                'translationRepository' => $this->menuItemTranslationRepository,
                'fk' => 'menu_item_id',
                'reference' => static fn (array $r, array $t = []): string => $reference($r, 'Menu Item #' . (int) ($r['id'] ?? 0), $t),
                'extra' => static fn (array $r): array => ['menu_id' => (int) ($r['menu_id'] ?? 0)],
                'fetch' => fn (): array => $this->menuItemRepository->getModel()
                    ->join('cms_menus m', 'm.id = cms_menu_items.menu_id')
                    ->where('m.deleted_at IS NULL')
                    ->select('cms_menu_items.*')
                    ->findAll(),
                'count' => fn (): int => (int) $this->menuItemRepository->getModel()
                    ->join('cms_menus m', 'm.id = cms_menu_items.menu_id')
                    ->where('m.deleted_at IS NULL')
                    ->countAllResults(),
            ],
            [
                'type' => 'collection',
                'repository' => $this->collectionRepository,
                'translationRepository' => $this->collectionTranslationRepository,
                'fk' => 'collection_id',
                'reference' => static fn (array $r, array $t = []): string => $reference($r, 'Collection #' . (int) ($r['id'] ?? 0), $t),
                'extra' => null,
                'fetch' => fn (): array => $this->collectionRepository->getModel()->findAll(),
                'count' => fn (): int => (int) $this->collectionRepository->getModel()->countAllResults(),
            ],
            [
                'type' => 'category',
                'repository' => $this->categoryRepository,
                'translationRepository' => $this->categoryTranslationRepository,
                'fk' => 'category_id',
                'reference' => static fn (array $r, array $t = []): string => $reference($r, 'Category #' . (int) ($r['id'] ?? 0), $t),
                'extra' => null,
                'fetch' => fn (): array => $this->categoryRepository->getModel()->findAll(),
                'count' => fn (): int => (int) $this->categoryRepository->getModel()->countAllResults(),
            ],
            [
                'type' => 'tag',
                'repository' => $this->tagRepository,
                'translationRepository' => $this->tagTranslationRepository,
                'fk' => 'tag_id',
                'reference' => static fn (array $r, array $t = []): string => $reference($r, 'Tag #' . (int) ($r['id'] ?? 0), $t),
                'extra' => null,
                'fetch' => fn (): array => $this->tagRepository->getModel()->findAll(),
                'count' => fn (): int => (int) $this->tagRepository->getModel()->countAllResults(),
            ],
            [
                'type' => 'entry',
                'repository' => $this->entryRepository,
                'translationRepository' => $this->entryTranslationRepository,
                'fk' => 'entry_id',
                'reference' => static fn (array $r, array $t = []): string => $reference($r, 'Entry #' . (int) ($r['id'] ?? 0), $t),
                'extra' => static fn (array $r): array => ['collection_id' => (int) ($r['collection_id'] ?? 0)],
                'fetch' => fn (): array => $this->entryRepository->getModel()->findAll(),
                'count' => fn (): int => (int) $this->entryRepository->getModel()->countAllResults(),
            ],
            [
                'type' => 'form',
                'repository' => $this->formRepository,
                'translationRepository' => $this->formTranslationRepository,
                'fk' => 'form_id',
                'reference' => static fn (array $r, array $t = []): string => $reference($r, 'Form #' . (int) ($r['id'] ?? 0), $t),
                'extra' => null,
                'fetch' => fn (): array => $this->formRepository->getModel()->findAll(),
                'count' => fn (): int => (int) $this->formRepository->getModel()->countAllResults(),
            ],
            [
                'type' => 'form_field',
                'repository' => $this->formFieldRepository,
                'translationRepository' => $this->formFieldTranslationRepository,
                'fk' => 'form_field_id',
                'reference' => static fn (array $r, array $t = []): string => $reference($r, 'Form Field #' . (int) ($r['id'] ?? 0), $t),
                'extra' => static fn (array $r): array => ['form_id' => (int) ($r['form_id'] ?? 0)],
                'fetch' => fn (): array => $this->formFieldRepository->getModel()->findAll(),
                'count' => fn (): int => (int) $this->formFieldRepository->getModel()->countAllResults(),
            ],
        ];
    }

    /**
     * @return array{
     *   type: string,
     *   repository: RepositoryInterface<object>,
     *   translationRepository: RepositoryInterface<object>,
     *   fk: string,
     *   reference: callable(array<string, mixed>, array<int, array<string, mixed>>): string,
     *   extra: null|callable(array<string, mixed>): array<string, mixed>,
     *   fetch: callable(): list<mixed>,
     *   count: callable(): int,
     * }|null
     */
    private function findSimpleResourceDescriptor(string $resourceType): ?array
    {
        foreach ($this->simpleResources as $descriptor) {
            if ($descriptor['type'] === $resourceType) {
                return $descriptor;
            }
        }

        return null;
    }

    /**
     * @param array{
     *   type: string,
     *   repository: RepositoryInterface<object>,
     *   translationRepository: RepositoryInterface<object>,
     *   fk: string,
     *   reference: callable(array<string, mixed>, array<int, array<string, mixed>>): string,
     *   extra: null|callable(array<string, mixed>): array<string, mixed>,
     *   fetch: callable(): list<mixed>,
     *   count: callable(): int,
     * } $descriptor
     * @param list<\App\Entities\LanguageEntity> $activeLanguages
     * @param array<string, mixed> $filters
     * @return list<array<string, mixed>>
     */
    private function auditSimpleResources(array $descriptor, array $activeLanguages, array $filters): array
    {
        $resources = ($descriptor['fetch'])();
        $translationsByResource = $this->support->groupTranslationsByResource(
            $descriptor['translationRepository']->getModel()->findAll(),
            $descriptor['fk']
        );
        $fieldDefinitions = TranslationResourceCatalog::fields($descriptor['type']);
        $valueResolver = static function (array $row, string $fieldKey, array $fieldDefinition): mixed {
            return $row[$fieldKey] ?? null;
        };

        $issues = [];
        foreach ($resources as $resource) {
            $resourceRow = $this->support->toArray($resource);
            $resourceId = (int) ($resourceRow['id'] ?? 0);
            if ($resourceId <= 0) {
                continue;
            }

            $translations = $translationsByResource[$resourceId] ?? [];
            foreach ($activeLanguages as $lang) {
                $langId = (int) $lang->id;
                if (! $this->support->languageFilterAllows($filters, $langId)) {
                    continue;
                }

                $translation = $translations[$langId] ?? null;
                [$status, $detail] = $this->support->evaluateTranslationState(
                    $translation,
                    $translations,
                    $fieldDefinitions,
                    $langId,
                    $valueResolver,
                    isset($resourceRow['updated_at']) ? (string) $resourceRow['updated_at'] : null
                );
                if ($status === 'complete') {
                    continue;
                }

                $issues[] = $this->support->buildIssue(
                    $descriptor['type'],
                    $resourceId,
                    ($descriptor['reference'])($resourceRow, $translations),
                    $langId,
                    (string) ($lang->code ?? ''),
                    $status,
                    $detail,
                    $descriptor['extra'] !== null ? ($descriptor['extra'])($resourceRow) : []
                );
            }
        }

        return $issues;
    }

    /**
     * @param list<\App\Entities\LanguageEntity> $activeLanguages
     * @param array<string, mixed> $filters
     * @return list<array<string, mixed>>
     */
    private function auditSettingTranslations(array $activeLanguages, array $filters): array
    {
        $settings = $this->settingRepository->getModel()->where('is_translatable', 1)->findAll();
        $translationsBySetting = $this->support->groupTranslationsByResource(
            $this->settingTranslationRepository->getModel()->findAll(),
            'setting_id'
        );
        $defaultLanguageId = $this->getDefaultLanguageId();
        $fieldDefinitions = TranslationResourceCatalog::fields('setting');
        $valueResolver = static function (array $row, string $fieldKey, array $fieldDefinition): mixed {
            return $row[$fieldKey] ?? null;
        };

        $issues = [];
        foreach ($settings as $setting) {
            $settingRow = $this->support->toArray($setting);
            $settingId = (int) ($settingRow['id'] ?? 0);
            if ($settingId <= 0) {
                continue;
            }

            $translations = $translationsBySetting[$settingId] ?? [];
            if ($defaultLanguageId !== null) {
                $translations[$defaultLanguageId] = ['setting_value' => $settingRow['setting_value'] ?? null];
            }

            foreach ($activeLanguages as $lang) {
                $langId = (int) $lang->id;
                if (! $this->support->languageFilterAllows($filters, $langId)) {
                    continue;
                }

                $translation = $langId === $defaultLanguageId
                    ? ['setting_value' => $settingRow['setting_value'] ?? null]
                    : ($translationsBySetting[$settingId][$langId] ?? null);

                [$status, $detail] = $this->support->evaluateTranslationState(
                    $translation,
                    $translations,
                    $fieldDefinitions,
                    $langId,
                    $valueResolver,
                    $langId === $defaultLanguageId ? null : (isset($settingRow['updated_at']) ? (string) $settingRow['updated_at'] : null)
                );
                if ($status === 'complete') {
                    continue;
                }

                $issues[] = $this->support->buildIssue(
                    'setting',
                    $settingId,
                    'Setting: ' . (string) ($settingRow['setting_key'] ?? $settingRow['id'] ?? ''),
                    $langId,
                    (string) ($lang->code ?? ''),
                    $status,
                    $detail
                );
            }
        }

        return $issues;
    }

    private function getDefaultLanguageId(): ?int
    {
        $language = $this->languageRepository->getModel()
            ->where('is_default', 1)
            ->where('is_active', 1)
            ->first();

        if (! $language) {
            return null;
        }

        return (int) ($language->id ?? 0);
    }

    private function countAuditableResources(): int
    {
        $total = 0;
        foreach ($this->simpleResources as $descriptor) {
            $total += ($descriptor['count'])();
        }

        return $total
            + (int) $this->settingRepository->getModel()->where('is_translatable', 1)->countAllResults()
            + $this->blockAuditor->countAuditable();
    }
}
