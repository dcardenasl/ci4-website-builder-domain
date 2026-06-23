<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\Interfaces\Cms\TranslationAuditServiceInterface;

class TranslationAuditService implements TranslationAuditServiceInterface
{
    protected \App\Models\LanguageModel $languageModel;
    protected \App\Models\PageModel $pageModel;
    protected \App\Models\PageTranslationModel $pageTranslationModel;
    protected \App\Models\MenuItemModel $menuItemModel;
    protected \App\Models\MenuItemTranslationModel $menuItemTranslationModel;
    protected \App\Models\SettingModel $settingModel;
    protected \App\Models\SettingTranslationModel $settingTranslationModel;

    public function __construct()
    {
        $this->languageModel = model(\App\Models\LanguageModel::class);
        $this->pageModel = model(\App\Models\PageModel::class);
        $this->pageTranslationModel = model(\App\Models\PageTranslationModel::class);
        $this->menuItemModel = model(\App\Models\MenuItemModel::class);
        $this->menuItemTranslationModel = model(\App\Models\MenuItemTranslationModel::class);
        $this->settingModel = model(\App\Models\SettingModel::class);
        $this->settingTranslationModel = model(\App\Models\SettingTranslationModel::class);
    }

    /**
     * {@inheritdoc}
     *
     * @return array<int, array<string, mixed>>
     */
    public function getOverallCompleteness(): array
    {
        $activeLanguages = $this->languageModel->where('is_active', 1)->findAll();
        if (empty($activeLanguages)) {
            return [];
        }

        $totalSettings = (int) $this->settingModel->where('is_translatable', 1)->countAllResults();
        $completedSettings = (int) $this->settingModel->where('is_translatable', 1)->where('setting_value !=', '')->countAllResults();

        $report = [];
        foreach ($activeLanguages as $lang) {
            /** @var \App\Models\LanguageModel $lang */
            $langId = (int) $lang->id;

            // Audit pages
            $totalPages = (int) $this->pageModel->countAllResults();
            $translatedPages = (int) $this->pageTranslationModel->builder()
                ->join('cms_pages p', 'p.id = cms_page_translations.page_id')
                ->where('p.deleted_at IS NULL')
                ->where('cms_page_translations.language_id', $langId)
                ->where('cms_page_translations.slug !=', '')
                ->where('cms_page_translations.title !=', '')
                ->countAllResults();

            // Audit menu items
            $totalMenuItems = (int) $this->menuItemModel
                ->join('cms_menus m', 'm.id = cms_menu_items.menu_id')
                ->where('m.deleted_at IS NULL')
                ->countAllResults();
            $translatedMenuItems = (int) $this->menuItemTranslationModel->builder()
                ->join('cms_menu_items mi', 'mi.id = cms_menu_item_translations.menu_item_id')
                ->join('cms_menus m', 'm.id = mi.menu_id')
                ->where('m.deleted_at IS NULL')
                ->where('cms_menu_item_translations.language_id', $langId)
                ->where('cms_menu_item_translations.label !=', '')
                ->countAllResults();

            $totalElements = $totalPages + $totalMenuItems + $totalSettings;
            $completedElements = $translatedPages + $translatedMenuItems + $completedSettings;
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
        $activeLanguages = $this->languageModel->where('is_active', 1)->findAll();
        if (empty($activeLanguages)) {
            return [];
        }

        $issues = [];

        // 1. Audit Pages
        $pages = $this->pageModel->findAll();
        foreach ($pages as $page) {
            /** @var \App\Models\PageModel $page */
            $pageId = (int) $page->id;
            $translations = $this->pageTranslationModel->where('page_id', $pageId)->findAll();
            $translationsByLang = [];
            foreach ($translations as $t) {
                $translationsByLang[(int) $t->language_id] = $t;
            }

            foreach ($activeLanguages as $lang) {
                /** @var \App\Models\LanguageModel $lang */
                $langId = (int) $lang->id;

                if (isset($filters['language_id']) && (int) $filters['language_id'] !== $langId) {
                    continue;
                }

                if (!isset($translationsByLang[$langId])) {
                    $issues[] = [
                        'resource' => 'page',
                        'resource_id' => $pageId,
                        'reference_name' => 'Page #' . $pageId . ' (Type: ' . $page->page_type . ')',
                        'language_id' => $langId,
                        'language_code' => $lang->code,
                        'status' => 'missing',
                        'detail' => 'Translation is missing completely'
                    ];
                } else {
                    /** @var \App\Models\PageTranslationModel $trans */
                    $trans = $translationsByLang[$langId];
                    $missing = [];
                    if (empty($trans->title)) {
                        $missing[] = 'title';
                    }
                    if (empty($trans->slug)) {
                        $missing[] = 'slug';
                    }

                    if (!empty($missing)) {
                        $issues[] = [
                            'resource' => 'page',
                            'resource_id' => $pageId,
                            'reference_name' => $trans->title ?: 'Page #' . $pageId,
                            'language_id' => $langId,
                            'language_code' => $lang->code,
                            'status' => 'incomplete',
                            'detail' => 'Missing fields: ' . implode(', ', $missing)
                        ];
                    }
                }
            }
        }

        // 2. Audit Menu Items
        $menuItems = $this->menuItemModel
            ->join('cms_menus m', 'm.id = cms_menu_items.menu_id')
            ->where('m.deleted_at IS NULL')
            ->select('cms_menu_items.*')
            ->findAll();
        foreach ($menuItems as $item) {
            /** @var \App\Models\MenuItemModel $item */
            $itemId = (int) $item->id;
            $translations = $this->menuItemTranslationModel->where('menu_item_id', $itemId)->findAll();
            $translationsByLang = [];
            foreach ($translations as $t) {
                $translationsByLang[(int) $t->language_id] = $t;
            }

            foreach ($activeLanguages as $lang) {
                /** @var \App\Models\LanguageModel $lang */
                $langId = (int) $lang->id;

                if (isset($filters['language_id']) && (int) $filters['language_id'] !== $langId) {
                    continue;
                }

                if (!isset($translationsByLang[$langId])) {
                    $issues[] = [
                        'resource' => 'menu_item',
                        'resource_id' => $itemId,
                        'reference_name' => 'Menu Item #' . $itemId,
                        'language_id' => $langId,
                        'language_code' => $lang->code,
                        'status' => 'missing',
                        'detail' => 'Translation is missing completely',
                        'extra_data' => ['menu_id' => (int) $item->menu_id]
                    ];
                } else {
                    $trans = $translationsByLang[$langId];
                    if (empty($trans->label)) {
                        $issues[] = [
                            'resource' => 'menu_item',
                            'resource_id' => $itemId,
                            'reference_name' => 'Menu Item #' . $itemId,
                            'language_id' => $langId,
                            'language_code' => $lang->code,
                            'status' => 'incomplete',
                            'detail' => 'Missing fields: label',
                            'extra_data' => ['menu_id' => (int) $item->menu_id]
                        ];
                    }
                }
            }
        }

        // 3. Audit Settings
        $settings = $this->settingModel->where('is_translatable', 1)->findAll();
        $baseLanguage = $this->languageModel->where('is_default', 1)->where('is_active', 1)->first();
        $baseLanguageId = $baseLanguage ? (int) $baseLanguage->id : null;
        foreach ($settings as $setting) {
            /** @var \App\Models\SettingModel $setting */
            $settingId = (int) $setting->id;
            if (empty($setting->setting_value)) {
                if (isset($filters['language_id']) && $baseLanguageId !== null && (int) $filters['language_id'] !== $baseLanguageId) {
                    continue;
                }

                $issues[] = [
                    'resource' => 'setting',
                    'resource_id' => $settingId,
                    'reference_name' => 'Setting: ' . $setting->setting_key,
                    'language_id' => $baseLanguageId,
                    'language_code' => $baseLanguage ? (string) ($baseLanguage->code ?? '') : '',
                    'status' => 'incomplete',
                    'detail' => 'Missing fields: setting_value',
                ];
            }

            $translations = $this->settingTranslationModel->where('setting_id', $settingId)->findAll();
            foreach ($translations as $trans) {
                /** @var \App\Models\SettingTranslationModel $trans */
                $langId = (int) $trans->language_id;
                if (isset($filters['language_id']) && (int) $filters['language_id'] !== $langId) {
                    continue;
                }

                if (empty($trans->setting_value)) {
                    $language = $this->languageModel->find($langId);
                    $issues[] = [
                        'resource' => 'setting',
                        'resource_id' => $settingId,
                        'reference_name' => 'Setting: ' . $setting->setting_key,
                        'language_id' => $langId,
                        'language_code' => is_object($language) ? (string) ($language->code ?? '') : '',
                        'status' => 'incomplete',
                        'detail' => 'Missing fields: setting_value'
                    ];
                }
            }
        }

        return $issues;
    }

    /**
     * {@inheritdoc}
     */
    public function auditResource(string $resourceType, int $resourceId): array
    {
        $activeLanguages = $this->languageModel->where('is_active', 1)->findAll();
        $report = [];

        foreach ($activeLanguages as $lang) {
            $langId = (int) $lang->id;
            $status = 'missing';
            $detail = '';

            switch ($resourceType) {
                case 'page':
                    $trans = $this->pageTranslationModel
                        ->where('page_id', $resourceId)
                        ->where('language_id', $langId)
                        ->first();
                    if ($trans) {
                        $missing = [];
                        if (empty($trans->title)) {
                            $missing[] = 'title';
                        }
                        if (empty($trans->slug)) {
                            $missing[] = 'slug';
                        }
                        $status = empty($missing) ? 'complete' : 'incomplete';
                        $detail = empty($missing) ? '' : 'Missing fields: ' . implode(', ', $missing);
                    }
                    break;

                case 'menu_item':
                    $trans = $this->menuItemTranslationModel
                        ->where('menu_item_id', $resourceId)
                        ->where('language_id', $langId)
                        ->first();
                    if ($trans) {
                        $status = empty($trans->label) ? 'incomplete' : 'complete';
                        $detail = empty($trans->label) ? 'Missing fields: label' : '';
                    }
                    break;

                case 'setting':
                    $setting = $this->settingModel->find($resourceId);
                    if ($setting && ! empty($setting->setting_value)) {
                        $status = 'complete';
                    } elseif ($setting) {
                        $status = 'incomplete';
                        $detail = 'Missing fields: setting_value';
                    }

                    $trans = $this->settingTranslationModel
                        ->where('setting_id', $resourceId)
                        ->where('language_id', $langId)
                        ->first();
                    if ($trans && empty($trans->setting_value)) {
                        $status = 'incomplete';
                        $detail = 'Missing fields: setting_value';
                    } elseif ($trans && ! empty($trans->setting_value) && $status !== 'incomplete') {
                        $status = 'complete';
                    }
                    break;
            }

            $report[$lang->code] = [
                'language_id' => $langId,
                'status' => $status,
                'detail' => $detail
            ];
        }

        return $report;
    }
}
