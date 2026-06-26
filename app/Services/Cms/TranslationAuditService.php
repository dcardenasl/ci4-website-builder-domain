<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\Interfaces\Cms\TranslationAuditServiceInterface;
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
    protected \App\Models\BlockTypeModel $blockTypeModel;
    protected \App\Models\BlockInstanceTranslationModel $blockInstanceTranslationModel;

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
        $this->blockTypeModel = model(\App\Models\BlockTypeModel::class);
        $this->blockInstanceTranslationModel = model(\App\Models\BlockInstanceTranslationModel::class);
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
        $issues = array_merge($issues, $this->auditBlockInstanceTranslations($activeLanguages, $filters));

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

                $translations = $this->groupTranslationsByResource(
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

                $translations = $this->groupTranslationsByResource(
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

                $translations = $this->groupTranslationsByResource(
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

                $translations = $this->groupTranslationsByResource(
                    $this->settingTranslationModel->where('setting_id', $resourceId)->findAll(),
                    'setting_id'
                )[$resourceId] ?? [];
                $fieldDefinitions = TranslationResourceCatalog::fields('setting');
                $defaultLanguageId = $this->getDefaultLanguageId();
                $resourceRow = $this->toArray($resource);
                if ($defaultLanguageId !== null) {
                    $translations[$defaultLanguageId] = ['setting_value' => $resourceRow['setting_value'] ?? null];
                }
                break;

            case 'collection':
                $resource = $this->collectionModel->find($resourceId);
                if ($resource === null) {
                    return [];
                }

                $translations = $this->groupTranslationsByResource(
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

                $translations = $this->groupTranslationsByResource(
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

                $translations = $this->groupTranslationsByResource(
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

                $translations = $this->groupTranslationsByResource(
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

                $translations = $this->groupTranslationsByResource(
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

                $translations = $this->groupTranslationsByResource(
                    $this->formFieldTranslationModel->where('form_field_id', $resourceId)->findAll(),
                    'form_field_id'
                )[$resourceId] ?? [];
                $fieldDefinitions = TranslationResourceCatalog::fields('form_field');
                break;

            case 'block_instance':
                $resource = $this->getBlockInstanceWithType($resourceId);
                if ($resource === null) {
                    return [];
                }

                $translations = $this->groupTranslationsByResource(
                    $this->blockInstanceTranslationModel->where('instance_id', $resourceId)->findAll(),
                    'instance_id'
                )[$resourceId] ?? [];
                $fieldDefinitions = $this->getTranslatableBlockFieldDefinitions($resource['schema_definition'] ?? null);
                $valueResolver = function (array $row, string $fieldKey, array $fieldDefinition): mixed {
                    return $this->extractBlockFieldValue($row, $fieldKey, $fieldDefinition);
                };
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
            [$status, $detail] = $this->evaluateTranslationState(
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
        $translationsByPage = $this->groupTranslationsByResource(
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
        $translationsByMenu = $this->groupTranslationsByResource(
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
        $translationsByItem = $this->groupTranslationsByResource(
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
        $translationsBySetting = $this->groupTranslationsByResource(
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
            $settingRow = $this->toArray($setting);
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
                if (! $this->languageFilterAllows($filters, $langId)) {
                    continue;
                }

                $translation = $langId === $defaultLanguageId
                    ? ['setting_value' => $settingRow['setting_value'] ?? null]
                    : ($translationsBySetting[$settingId][$langId] ?? null);

                [$status, $detail] = $this->evaluateTranslationState(
                    $translation,
                    $translations,
                    $fieldDefinitions,
                    $langId,
                    $valueResolver
                );
                if ($status === 'complete') {
                    continue;
                }

                $issues[] = $this->buildIssue(
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
        $translationsByCollection = $this->groupTranslationsByResource(
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
        $translationsByCategory = $this->groupTranslationsByResource(
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
        $translationsByTag = $this->groupTranslationsByResource(
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
        $translationsByEntry = $this->groupTranslationsByResource(
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
        $translationsByForm = $this->groupTranslationsByResource(
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
        $translationsByField = $this->groupTranslationsByResource(
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
     * @param list<object> $activeLanguages
     * @param array<string, mixed> $filters
     * @return list<array<string, mixed>>
     */
    private function auditBlockInstanceTranslations(array $activeLanguages, array $filters): array
    {
        $instances = $this->getBlockInstancesWithTypes();
        $translationsByInstance = $this->groupTranslationsByResource(
            $this->blockInstanceTranslationModel->findAll(),
            'instance_id'
        );

        $issues = [];
        foreach ($instances as $instance) {
            $instanceId = (int) ($instance['id'] ?? 0);
            if ($instanceId <= 0) {
                continue;
            }

            $translatableFields = $this->getTranslatableBlockFieldDefinitions($instance['schema_definition'] ?? null);
            if ($translatableFields === []) {
                continue;
            }

            $translations = $translationsByInstance[$instanceId] ?? [];
            foreach ($activeLanguages as $lang) {
                $langId = (int) $lang->id;
                if (! $this->languageFilterAllows($filters, $langId)) {
                    continue;
                }

                $translation = $translations[$langId] ?? null;
                [$status, $detail] = $this->evaluateTranslationState(
                    $translation,
                    $translations,
                    $translatableFields,
                    $langId,
                    function (array $row, string $fieldKey, array $fieldDefinition): mixed {
                        return $this->extractBlockFieldValue($row, $fieldKey, $fieldDefinition);
                    }
                );
                if ($status === 'complete') {
                    continue;
                }

                $issues[] = $this->buildIssue(
                    'block_instance',
                    $instanceId,
                    'Block Instance #' . $instanceId . ' (' . (string) ($instance['block_key'] ?? '') . ')',
                    $langId,
                    (string) ($lang->code ?? ''),
                    $status,
                    $detail,
                    [
                        'owner_type' => (string) ($instance['owner_type'] ?? ''),
                        'owner_id' => (int) ($instance['owner_id'] ?? 0),
                        'block_key' => (string) ($instance['block_key'] ?? ''),
                    ]
                );
            }
        }

        return $issues;
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
            $resourceRow = $this->toArray($resource);
            $resourceId = (int) ($resourceRow['id'] ?? 0);
            if ($resourceId <= 0) {
                continue;
            }

            $translations = $translationsByResource[$resourceId] ?? [];
            foreach ($activeLanguages as $lang) {
                $langId = (int) $lang->id;
                if (! $this->languageFilterAllows($filters, $langId)) {
                    continue;
                }

                $translation = $translations[$langId] ?? null;
                [$status, $detail] = $this->evaluateTranslationState(
                    $translation,
                    $translations,
                    $fieldDefinitions,
                    $langId,
                    $valueResolver
                );
                if ($status === 'complete') {
                    continue;
                }

                $issues[] = $this->buildIssue(
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

    /**
     * @param array<string, mixed>|object|null $translation
     * @param list<string> $requiredFields
     * @return array{0: string, 1: string}
     */
    private function evaluateRequiredFields(array|object|null $translation, array $requiredFields): array
    {
        $fieldDefinitions = [];
        foreach ($requiredFields as $field) {
            $fieldDefinitions[$field] = ['required' => true];
        }

        return $this->evaluateTranslationState(
            $translation,
            [],
            $fieldDefinitions,
            0,
            static function (array $row, string $fieldKey, array $fieldDefinition): mixed {
                return $row[$fieldKey] ?? null;
            }
        );
    }

    /**
     * @param array<string, mixed>|object $resource
     * @return array<string, mixed>
     */
    private function toArray(array|object $resource): array
    {
        if (is_array($resource)) {
            return $resource;
        }

        if (method_exists($resource, 'toArray')) {
            /** @var array<string, mixed> $data */
            $data = $resource->toArray();

            return $data;
        }

        return (array) $resource;
    }

    /**
     * @param array<string, mixed>|object|null $translation
     * @return array<string, mixed>
     */
    private function normalizeTranslation(array|object|null $translation): ?array
    {
        if ($translation === null) {
            return null;
        }

        return is_array($translation) ? $translation : (array) $translation;
    }

    /**
     * @param list<array<string, mixed>|object> $rows
     * @return array<int, array<int, array<string, mixed>>>
     */
    private function groupTranslationsByResource(array $rows, string $foreignKey): array
    {
        $indexed = [];
        foreach ($rows as $row) {
            $data = $this->toArray($row);
            $resourceId = (int) ($data[$foreignKey] ?? 0);
            $langId = (int) ($data['language_id'] ?? 0);
            if ($resourceId <= 0 || $langId <= 0) {
                continue;
            }

            $indexed[$resourceId][$langId] = $data;
        }

        return $indexed;
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function languageFilterAllows(array $filters, int $languageId): bool
    {
        if (!isset($filters['language_id'])) {
            return true;
        }

        return (int) $filters['language_id'] === $languageId;
    }

    private function isBlank(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        if (is_array($value)) {
            return $value === [];
        }

        return false;
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
     * @return array<string, mixed>
     */
    private function buildIssue(
        string $resourceType,
        int $resourceId,
        string $referenceName,
        int $languageId,
        string $languageCode,
        string $status,
        string $detail,
        array $extraData = []
    ): array {
        $issue = [
            'resource' => $resourceType,
            'resource_id' => $resourceId,
            'reference_name' => $referenceName,
            'language_id' => $languageId,
            'language_code' => $languageCode,
            'status' => $status,
            'detail' => $detail,
        ];

        if ($extraData !== []) {
            $issue['extra_data'] = $extraData;
        }

        return $issue;
    }

    private function auditSettingResource(int $settingId, int $languageId): array
    {
        $setting = $this->settingModel->where('is_translatable', 1)->find($settingId);
        if (! $setting) {
            return ['missing', 'Translation is missing completely'];
        }

        $settingRow = $this->toArray($setting);
        $translations = $this->groupTranslationsByResource(
            $this->settingTranslationModel
                ->where('setting_id', $settingId)
                ->findAll(),
            'setting_id'
        )[$settingId] ?? [];

        $defaultLanguageId = $this->getDefaultLanguageId();
        if ($defaultLanguageId !== null) {
            $translations[$defaultLanguageId] = ['setting_value' => $settingRow['setting_value'] ?? null];
        }

        $translation = $languageId === $defaultLanguageId
            ? ['setting_value' => $settingRow['setting_value'] ?? null]
            : ($translations[$languageId] ?? null);

        return $this->evaluateTranslationState(
            $translation,
            $translations,
            TranslationResourceCatalog::fields('setting'),
            $languageId,
            static function (array $row, string $fieldKey, array $fieldDefinition): mixed {
                return $row[$fieldKey] ?? null;
            }
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getBlockInstancesWithTypes(): array
    {
        $db = \Config\Database::connect();
        $query = $db->table('cms_block_instances i')
            ->select('i.*, b.block_key, b.schema_definition')
            ->join('cms_content_blocks b', 'b.id = i.block_id')
            ->where('i.is_active', 1)
            ->orderBy('i.sort_order', 'ASC')
            ->get();

        return $query ? $query->getResultArray() : [];
    }

    /**
     * @param array<string, mixed>|array<int, mixed>|string|null $schemaDefinition
     * @return array<string, array{required: bool, type: string, data_key: string}>
     */
    private function getTranslatableBlockFieldDefinitions(mixed $schemaDefinition): array
    {
        $schema = is_string($schemaDefinition)
            ? json_decode($schemaDefinition, true)
            : (is_array($schemaDefinition) ? $schemaDefinition : []);

        if (!is_array($schema)) {
            return [];
        }

        $fields = $schema['fields'] ?? [];
        if (!is_array($fields) || $fields === []) {
            return [];
        }

        $translatable = [];
        foreach ($fields as $fieldKey => $fieldDef) {
            if (!is_array($fieldDef)) {
                continue;
            }

            if (!TranslationResourceCatalog::isAuditableBlockField($fieldDef)) {
                continue;
            }

            $fieldKey = (string) $fieldKey;
            $translatable[$fieldKey] = [
                'required' => (bool) ($fieldDef['required'] ?? false),
                'type' => strtolower((string) ($fieldDef['type'] ?? 'string')),
                'data_key' => TranslationResourceCatalog::blockDataKey($fieldKey, $fieldDef),
            ];
        }

        return $translatable;
    }

    /**
     * @param array<string, mixed>|array<int, mixed>|string|null $schemaDefinition
     * @return list<string>
     */
    private function getTranslatableBlockFields(mixed $schemaDefinition): array
    {
        return array_keys($this->getTranslatableBlockFieldDefinitions($schemaDefinition));
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function auditSingleBlockInstance(int $resourceId, int $languageId): array
    {
        $instance = $this->getBlockInstanceWithType($resourceId);
        if (!is_array($instance)) {
            return ['missing', 'Translation is missing completely'];
        }

        $fields = $this->getTranslatableBlockFieldDefinitions($instance['schema_definition'] ?? null);
        if ($fields === []) {
            return ['complete', ''];
        }

        $translations = $this->groupTranslationsByResource(
            $this->blockInstanceTranslationModel
                ->where('instance_id', $resourceId)
                ->findAll(),
            'instance_id'
        )[$resourceId] ?? [];

        return $this->evaluateTranslationState(
            $translations[$languageId] ?? null,
            $translations,
            $fields,
            $languageId,
            function (array $row, string $fieldKey, array $fieldDefinition): mixed {
                return $this->extractBlockFieldValue($row, $fieldKey, $fieldDefinition);
            }
        );
    }

    /**
     * @param array<string, mixed>|object|null $translation
     * @param list<string> $requiredFields
     * @return array{0: string, 1: string}
     */
    private function evaluateTranslationState(
        array|object|null $translation,
        array $translationsByLanguage,
        array $fieldDefinitions,
        int $languageId,
        callable $valueResolver
    ): array {
        if ($translation === null) {
            return ['missing', 'Translation is missing completely'];
        }

        $row = $this->toArray($translation);
        $missingRequired = [];
        $mismatchedOptional = [];

        foreach ($fieldDefinitions as $fieldKey => $fieldDefinition) {
            $fieldKey = (string) $fieldKey;
            $fieldDefinition = is_array($fieldDefinition) ? $fieldDefinition : [];
            $currentValue = $valueResolver($row, $fieldKey, $fieldDefinition);

            if ($this->isBlank($currentValue)) {
                if ((bool) ($fieldDefinition['required'] ?? false)) {
                    $missingRequired[] = $fieldKey;
                    continue;
                }

                foreach ($translationsByLanguage as $otherLanguageId => $otherTranslation) {
                    if ((int) $otherLanguageId === $languageId || $otherTranslation === null) {
                        continue;
                    }

                    $otherRow = $this->toArray($otherTranslation);
                    $otherValue = $valueResolver($otherRow, $fieldKey, $fieldDefinition);
                    if (! $this->isBlank($otherValue)) {
                        $mismatchedOptional[] = $fieldKey;
                        break;
                    }
                }
            }
        }

        $missingRequired = array_values(array_unique($missingRequired));
        if ($missingRequired !== []) {
            return ['incomplete', 'Missing required fields: ' . implode(', ', $missingRequired)];
        }

        $mismatchedOptional = array_values(array_unique($mismatchedOptional));
        if ($mismatchedOptional !== []) {
            return ['mismatch', 'Inconsistent fields: ' . implode(', ', $mismatchedOptional)];
        }

        return ['complete', ''];
    }

    /**
     * @param array<string, mixed> $row
     */
    private function extractBlockFieldValue(array $row, string $fieldKey, array $fieldDefinition): mixed
    {
        $blockData = $row['block_data'] ?? null;
        if (is_string($blockData)) {
            $decoded = json_decode($blockData, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $blockData = $decoded;
            }
        }

        if (is_object($blockData)) {
            $blockData = (array) $blockData;
        }

        if (!is_array($blockData)) {
            $blockData = [];
        }

        $dataKey = (string) ($fieldDefinition['data_key'] ?? $fieldKey);

        return $blockData[$dataKey] ?? null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getBlockInstanceWithType(int $resourceId): ?array
    {
        $db = \Config\Database::connect();
        $query = $db->table('cms_block_instances i')
            ->select('i.*, b.block_key, b.schema_definition')
            ->join('cms_content_blocks b', 'b.id = i.block_id')
            ->where('i.id', $resourceId)
            ->limit(1)
            ->get();

        $instance = $query ? $query->getRowArray() : null;

        return is_array($instance) ? $instance : null;
    }

    /**
     * @param array<string, mixed>|object|null $translation
     * @return array{0: string, 1: string}
     */
    private function evaluateBlockDataFields(array|object|null $translation, array $requiredFields): array
    {
        if ($translation === null) {
            return ['missing', 'Translation is missing completely'];
        }

        $row = $this->toArray($translation);
        $blockData = $row['block_data'] ?? null;
        if (is_string($blockData)) {
            $decoded = json_decode($blockData, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $blockData = $decoded;
            }
        }

        if (is_object($blockData)) {
            $blockData = (array) $blockData;
        }

        if (!is_array($blockData)) {
            $blockData = [];
        }

        $missing = [];
        foreach ($requiredFields as $field) {
            if ($this->isBlank($blockData[$field] ?? null)) {
                $missing[] = $field;
            }
        }

        if ($missing === []) {
            return ['complete', ''];
        }

        return ['incomplete', 'Missing fields: ' . implode(', ', $missing)];
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
            + $this->countAuditableBlockInstances();
    }

    private function countAuditableBlockInstances(): int
    {
        $instances = $this->getBlockInstancesWithTypes();
        $count = 0;

        foreach ($instances as $instance) {
            if ($this->getTranslatableBlockFields($instance['schema_definition'] ?? null) !== []) {
                $count++;
            }
        }

        return $count;
    }
}
