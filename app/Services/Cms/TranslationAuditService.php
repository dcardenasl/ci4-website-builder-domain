<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\Interfaces\Cms\TranslationAuditServiceInterface;
use App\Libraries\Cms\TranslationAuditSupport;
use App\Libraries\Cms\TranslationResourceCatalog;

class TranslationAuditService implements TranslationAuditServiceInterface
{
    protected \App\Models\LanguageModel $languageModel;
    protected \App\Models\PageModel $pageModel;
    protected \App\Models\PageTranslationModel $pageTranslationModel;
    protected \App\Models\MenuModel $menuModel;
    protected \App\Models\MenuTranslationModel $menuTranslationModel;
    protected \App\Models\MenuItemModel $menuItemModel;
    protected \App\Models\MenuItemTranslationModel $menuItemTranslationModel;
    protected \App\Models\SettingModel $settingModel;
    protected \App\Models\SettingTranslationModel $settingTranslationModel;
    protected \App\Models\CollectionModel $collectionModel;
    protected \App\Models\CollectionTranslationModel $collectionTranslationModel;
    protected \App\Models\CategoryModel $categoryModel;
    protected \App\Models\CategoryTranslationModel $categoryTranslationModel;
    protected \App\Models\TagModel $tagModel;
    protected \App\Models\TagTranslationModel $tagTranslationModel;
    protected \App\Models\EntryModel $entryModel;
    protected \App\Models\EntryTranslationModel $entryTranslationModel;
    protected \App\Models\FormModel $formModel;
    protected \App\Models\FormTranslationModel $formTranslationModel;
    protected \App\Models\FormFieldModel $formFieldModel;
    protected \App\Models\FormFieldTranslationModel $formFieldTranslationModel;
    protected TranslationAuditSupport $support;
    protected BlockInstanceTranslationAuditor $blockAuditor;

    public function __construct()
    {
        $this->languageModel = model(\App\Models\LanguageModel::class);
        $this->pageModel = model(\App\Models\PageModel::class);
        $this->pageTranslationModel = model(\App\Models\PageTranslationModel::class);
        $this->menuModel = model(\App\Models\MenuModel::class);
        $this->menuTranslationModel = model(\App\Models\MenuTranslationModel::class);
        $this->menuItemModel = model(\App\Models\MenuItemModel::class);
        $this->menuItemTranslationModel = model(\App\Models\MenuItemTranslationModel::class);
        $this->settingModel = model(\App\Models\SettingModel::class);
        $this->settingTranslationModel = model(\App\Models\SettingTranslationModel::class);
        $this->collectionModel = model(\App\Models\CollectionModel::class);
        $this->collectionTranslationModel = model(\App\Models\CollectionTranslationModel::class);
        $this->categoryModel = model(\App\Models\CategoryModel::class);
        $this->categoryTranslationModel = model(\App\Models\CategoryTranslationModel::class);
        $this->tagModel = model(\App\Models\TagModel::class);
        $this->tagTranslationModel = model(\App\Models\TagTranslationModel::class);
        $this->entryModel = model(\App\Models\EntryModel::class);
        $this->entryTranslationModel = model(\App\Models\EntryTranslationModel::class);
        $this->formModel = model(\App\Models\FormModel::class);
        $this->formTranslationModel = model(\App\Models\FormTranslationModel::class);
        $this->formFieldModel = model(\App\Models\FormFieldModel::class);
        $this->formFieldTranslationModel = model(\App\Models\FormFieldTranslationModel::class);
        $this->support = new TranslationAuditSupport();
        $this->blockAuditor = new BlockInstanceTranslationAuditor($this->support);
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
            /** @var \App\Models\LanguageModel $lang */
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
        $issues = array_merge($issues, $this->auditPageTranslations($activeLanguages, $filters));
        $issues = array_merge($issues, $this->auditMenuTranslations($activeLanguages, $filters));
        $issues = array_merge($issues, $this->auditMenuItemTranslations($activeLanguages, $filters));
        $issues = array_merge($issues, $this->auditSettingTranslations($activeLanguages, $filters));
        $issues = array_merge($issues, $this->auditCollectionTranslations($activeLanguages, $filters));
        $issues = array_merge($issues, $this->auditCategoryTranslations($activeLanguages, $filters));
        $issues = array_merge($issues, $this->auditTagTranslations($activeLanguages, $filters));
        $issues = array_merge($issues, $this->auditEntryTranslations($activeLanguages, $filters));
        $issues = array_merge($issues, $this->auditFormTranslations($activeLanguages, $filters));
        $issues = array_merge($issues, $this->auditFormFieldTranslations($activeLanguages, $filters));
        $issues = array_merge($issues, $this->blockAuditor->audit($activeLanguages, $filters));

        return $issues;
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

        switch ($resourceType) {
            case 'page':
                $resource = $this->pageModel->find($resourceId);
                if ($resource === null) {
                    return [];
                }

                $translations = $this->support->groupTranslationsByResource(
                    $this->pageTranslationModel->where('page_id', $resourceId)->findAll(),
                    'page_id'
                )[$resourceId] ?? [];
                $fieldDefinitions = TranslationResourceCatalog::fields('page');
                break;

            case 'menu':
                $resource = $this->menuModel->find($resourceId);
                if ($resource === null) {
                    return [];
                }

                $translations = $this->support->groupTranslationsByResource(
                    $this->menuTranslationModel->where('menu_id', $resourceId)->findAll(),
                    'menu_id'
                )[$resourceId] ?? [];
                $fieldDefinitions = TranslationResourceCatalog::fields('menu');
                break;

            case 'menu_item':
                $resource = $this->menuItemModel->find($resourceId);
                if ($resource === null) {
                    return [];
                }

                $translations = $this->support->groupTranslationsByResource(
                    $this->menuItemTranslationModel->where('menu_item_id', $resourceId)->findAll(),
                    'menu_item_id'
                )[$resourceId] ?? [];
                $fieldDefinitions = TranslationResourceCatalog::fields('menu_item');
                break;

            case 'setting':
                $resource = $this->settingModel->where('is_translatable', 1)->find($resourceId);
                if ($resource === null) {
                    return [];
                }

                $translations = $this->support->groupTranslationsByResource(
                    $this->settingTranslationModel->where('setting_id', $resourceId)->findAll(),
                    'setting_id'
                )[$resourceId] ?? [];
                $fieldDefinitions = TranslationResourceCatalog::fields('setting');
                $defaultLanguageId = $this->getDefaultLanguageId();
                $resourceRow = $this->support->toArray($resource);
                if ($defaultLanguageId !== null) {
                    $translations[$defaultLanguageId] = ['setting_value' => $resourceRow['setting_value'] ?? null];
                }
                break;

            case 'collection':
                $resource = $this->collectionModel->find($resourceId);
                if ($resource === null) {
                    return [];
                }

                $translations = $this->support->groupTranslationsByResource(
                    $this->collectionTranslationModel->where('collection_id', $resourceId)->findAll(),
                    'collection_id'
                )[$resourceId] ?? [];
                $fieldDefinitions = TranslationResourceCatalog::fields('collection');
                break;

            case 'category':
                $resource = $this->categoryModel->find($resourceId);
                if ($resource === null) {
                    return [];
                }

                $translations = $this->support->groupTranslationsByResource(
                    $this->categoryTranslationModel->where('category_id', $resourceId)->findAll(),
                    'category_id'
                )[$resourceId] ?? [];
                $fieldDefinitions = TranslationResourceCatalog::fields('category');
                break;

            case 'tag':
                $resource = $this->tagModel->find($resourceId);
                if ($resource === null) {
                    return [];
                }

                $translations = $this->support->groupTranslationsByResource(
                    $this->tagTranslationModel->where('tag_id', $resourceId)->findAll(),
                    'tag_id'
                )[$resourceId] ?? [];
                $fieldDefinitions = TranslationResourceCatalog::fields('tag');
                break;

            case 'entry':
                $resource = $this->entryModel->find($resourceId);
                if ($resource === null) {
                    return [];
                }

                $translations = $this->support->groupTranslationsByResource(
                    $this->entryTranslationModel->where('entry_id', $resourceId)->findAll(),
                    'entry_id'
                )[$resourceId] ?? [];
                $fieldDefinitions = TranslationResourceCatalog::fields('entry');
                break;

            case 'form':
                $resource = $this->formModel->find($resourceId);
                if ($resource === null) {
                    return [];
                }

                $translations = $this->support->groupTranslationsByResource(
                    $this->formTranslationModel->where('form_id', $resourceId)->findAll(),
                    'form_id'
                )[$resourceId] ?? [];
                $fieldDefinitions = TranslationResourceCatalog::fields('form');
                break;

            case 'form_field':
                $resource = $this->formFieldModel->find($resourceId);
                if ($resource === null) {
                    return [];
                }

                $translations = $this->support->groupTranslationsByResource(
                    $this->formFieldTranslationModel->where('form_field_id', $resourceId)->findAll(),
                    'form_field_id'
                )[$resourceId] ?? [];
                $fieldDefinitions = TranslationResourceCatalog::fields('form_field');
                break;

            case 'block_instance':
                $resolved = $this->blockAuditor->resolveForResource($resourceId);
                if ($resolved === null) {
                    return [];
                }

                [$resource, $fieldDefinitions, $translations, $valueResolver] = $resolved;
                break;

            default:
                return [];
        }

        $report = [];
        foreach ($activeLanguages as $lang) {
            $langId = (int) $lang->id;
            if ($resourceType === 'setting' && isset($defaultLanguageId) && $defaultLanguageId === $langId) {
                $translation = ['setting_value' => $resourceRow['setting_value'] ?? null];
            } else {
                $translation = $translations[$langId] ?? null;
            }
            [$status, $detail] = $this->support->evaluateTranslationState(
                $translation,
                $translations,
                $fieldDefinitions,
                $langId,
                $valueResolver
            );

            $report[$lang->code] = [
                'language_id' => $langId,
                'status' => $status,
                'detail' => $detail,
            ];
        }

        return $report;
    }

    /**
     * @return list<object>
     */
    private function getActiveLanguages(): array
    {
        return $this->languageModel->where('is_active', 1)->findAll();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function auditPageTranslations(array $activeLanguages, array $filters): array
    {
        $pages = $this->pageModel->findAll();
        $translationsByPage = $this->support->groupTranslationsByResource(
            $this->pageTranslationModel->findAll(),
            'page_id'
        );

        return $this->auditSimpleResources(
            'page',
            $pages,
            $translationsByPage,
            $activeLanguages,
            ['title', 'slug'],
            static function (array $resource): string {
                return 'Page #' . (int) ($resource['id'] ?? 0) . ' (Type: ' . (string) ($resource['page_type'] ?? 'generic') . ')';
            },
            null,
            $filters
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function auditMenuTranslations(array $activeLanguages, array $filters): array
    {
        $menus = $this->menuModel->findAll();
        $translationsByMenu = $this->support->groupTranslationsByResource(
            $this->menuTranslationModel->findAll(),
            'menu_id'
        );

        return $this->auditSimpleResources(
            'menu',
            $menus,
            $translationsByMenu,
            $activeLanguages,
            ['name'],
            static function (array $resource): string {
                return 'Menu #' . (int) ($resource['id'] ?? 0) . ' (' . (string) ($resource['menu_key'] ?? '') . ')';
            },
            null,
            $filters
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function auditMenuItemTranslations(array $activeLanguages, array $filters): array
    {
        $menuItems = $this->menuItemModel
            ->join('cms_menus m', 'm.id = cms_menu_items.menu_id')
            ->where('m.deleted_at IS NULL')
            ->select('cms_menu_items.*')
            ->findAll();
        $translationsByItem = $this->support->groupTranslationsByResource(
            $this->menuItemTranslationModel->findAll(),
            'menu_item_id'
        );

        return $this->auditSimpleResources(
            'menu_item',
            $menuItems,
            $translationsByItem,
            $activeLanguages,
            ['label'],
            static function (array $resource): string {
                return 'Menu Item #' . (int) ($resource['id'] ?? 0);
            },
            static function (array $resource): array {
                return ['menu_id' => (int) ($resource['menu_id'] ?? 0)];
            },
            $filters
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function auditSettingTranslations(array $activeLanguages, array $filters): array
    {
        $settings = $this->settingModel->where('is_translatable', 1)->findAll();
        $translationsBySetting = $this->support->groupTranslationsByResource(
            $this->settingTranslationModel->findAll(),
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
                    $valueResolver
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

    /**
     * @return list<array<string, mixed>>
     */
    private function auditCollectionTranslations(array $activeLanguages, array $filters): array
    {
        $collections = $this->collectionModel->findAll();
        $translationsByCollection = $this->support->groupTranslationsByResource(
            $this->collectionTranslationModel->findAll(),
            'collection_id'
        );

        return $this->auditSimpleResources(
            'collection',
            $collections,
            $translationsByCollection,
            $activeLanguages,
            ['name', 'slug'],
            static function (array $resource): string {
                return 'Collection #' . (int) ($resource['id'] ?? 0) . ' (' . (string) ($resource['collection_key'] ?? '') . ')';
            },
            null,
            $filters
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function auditCategoryTranslations(array $activeLanguages, array $filters): array
    {
        $categories = $this->categoryModel->findAll();
        $translationsByCategory = $this->support->groupTranslationsByResource(
            $this->categoryTranslationModel->findAll(),
            'category_id'
        );

        return $this->auditSimpleResources(
            'category',
            $categories,
            $translationsByCategory,
            $activeLanguages,
            ['name', 'slug'],
            static function (array $resource): string {
                return 'Category #' . (int) ($resource['id'] ?? 0);
            },
            null,
            $filters
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function auditTagTranslations(array $activeLanguages, array $filters): array
    {
        $tags = $this->tagModel->findAll();
        $translationsByTag = $this->support->groupTranslationsByResource(
            $this->tagTranslationModel->findAll(),
            'tag_id'
        );

        return $this->auditSimpleResources(
            'tag',
            $tags,
            $translationsByTag,
            $activeLanguages,
            ['name', 'slug'],
            static function (array $resource): string {
                return 'Tag #' . (int) ($resource['id'] ?? 0);
            },
            null,
            $filters
        );
    }

    /**
     * @param list<object> $activeLanguages
     * @param array<string, mixed> $filters
     * @return list<array<string, mixed>>
     */
    private function auditEntryTranslations(array $activeLanguages, array $filters): array
    {
        $entries = $this->entryModel->findAll();
        $translationsByEntry = $this->support->groupTranslationsByResource(
            $this->entryTranslationModel->findAll(),
            'entry_id'
        );

        return $this->auditSimpleResources(
            'entry',
            $entries,
            $translationsByEntry,
            $activeLanguages,
            ['title', 'slug'],
            static function (array $resource): string {
                return 'Entry #' . (int) ($resource['id'] ?? 0);
            },
            static function (array $resource): array {
                return ['collection_id' => (int) ($resource['collection_id'] ?? 0)];
            },
            $filters
        );
    }

    /**
     * @param list<object> $activeLanguages
     * @param array<string, mixed> $filters
     * @return list<array<string, mixed>>
     */
    private function auditFormTranslations(array $activeLanguages, array $filters): array
    {
        $forms = $this->formModel->findAll();
        $translationsByForm = $this->support->groupTranslationsByResource(
            $this->formTranslationModel->findAll(),
            'form_id'
        );

        return $this->auditSimpleResources(
            'form',
            $forms,
            $translationsByForm,
            $activeLanguages,
            ['name', 'submit_label'],
            static function (array $resource): string {
                return 'Form #' . (int) ($resource['id'] ?? 0) . ' (' . (string) ($resource['form_key'] ?? '') . ')';
            },
            null,
            $filters
        );
    }

    /**
     * @param list<object> $activeLanguages
     * @param array<string, mixed> $filters
     * @return list<array<string, mixed>>
     */
    private function auditFormFieldTranslations(array $activeLanguages, array $filters): array
    {
        $fields = $this->formFieldModel->findAll();
        $translationsByField = $this->support->groupTranslationsByResource(
            $this->formFieldTranslationModel->findAll(),
            'form_field_id'
        );

        return $this->auditSimpleResources(
            'form_field',
            $fields,
            $translationsByField,
            $activeLanguages,
            ['label'],
            static function (array $resource): string {
                return 'Form Field #' . (int) ($resource['id'] ?? 0) . ' (' . (string) ($resource['field_key'] ?? '') . ')';
            },
            static function (array $resource): array {
                return ['form_id' => (int) ($resource['form_id'] ?? 0)];
            },
            $filters
        );
    }

    /**
     * @param list<object|array<string, mixed>> $resources
     * @param array<int, array<int, array<string, mixed>>> $translationsByResource
     * @param list<object> $activeLanguages
     * @param list<string> $requiredFields
     * @param callable(array<string, mixed>): string $referenceBuilder
     * @param null|callable(array<string, mixed>): array<string, mixed> $extraDataBuilder
     * @param array<string, mixed> $filters
     * @return list<array<string, mixed>>
     */
    private function auditSimpleResources(
        string $resourceType,
        array $resources,
        array $translationsByResource,
        array $activeLanguages,
        array $requiredFields,
        callable $referenceBuilder,
        ?callable $extraDataBuilder,
        array $filters
    ): array {
        $issues = [];
        $fieldDefinitions = TranslationResourceCatalog::fields($resourceType);
        $valueResolver = static function (array $row, string $fieldKey, array $fieldDefinition): mixed {
            return $row[$fieldKey] ?? null;
        };

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
                    $valueResolver
                );
                if ($status === 'complete') {
                    continue;
                }

                $issues[] = $this->support->buildIssue(
                    $resourceType,
                    $resourceId,
                    $referenceBuilder($resourceRow),
                    $langId,
                    (string) ($lang->code ?? ''),
                    $status,
                    $detail,
                    $extraDataBuilder !== null ? $extraDataBuilder($resourceRow) : []
                );
            }
        }

        return $issues;
    }

    private function getDefaultLanguageId(): ?int
    {
        $language = $this->languageModel
            ->where('is_default', 1)
            ->where('is_active', 1)
            ->first();

        if (! $language) {
            return null;
        }

        return (int) ($language->id ?? 0);
    }

    /**
     * @return int
     */
    private function countAuditableResources(): int
    {
        return (int) $this->pageModel->countAllResults()
            + (int) $this->menuModel->countAllResults()
            + (int) $this->menuItemModel
                ->join('cms_menus m', 'm.id = cms_menu_items.menu_id')
                ->where('m.deleted_at IS NULL')
                ->countAllResults()
            + (int) $this->settingModel->where('is_translatable', 1)->countAllResults()
            + (int) $this->collectionModel->countAllResults()
            + (int) $this->categoryModel->countAllResults()
            + (int) $this->tagModel->countAllResults()
            + (int) $this->entryModel->countAllResults()
            + (int) $this->formModel->countAllResults()
            + (int) $this->formFieldModel->countAllResults()
            + $this->blockAuditor->countAuditable();
    }
}
