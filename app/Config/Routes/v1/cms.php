<?php

declare (strict_types=1);
/** @var \CodeIgniter\Router\RouteCollection $routes */
$routes->group('cms', ['namespace' => '\App\Controllers\Api\V1\Cms'], function ($routes): void {
    // Public site discovery endpoint. It exposes only active language metadata.
    $routes->get('public/languages', 'PublicLanguageController::index');

    // Auth & Admin Protected Group
    $routes->group('', ['filter' => ['domainauth', 'throttle']], function ($routes): void {
        // Wizard Config (must be before any (:segment) routes)
        $routes->get('wizard/config', 'WizardConfigController::config', ['filter' => 'permission:cms.entries.read']);

        // Menus CRUD
        $routes->get('menus', 'MenuController::index', ['filter' => 'permission:cms.menus.read']);
        $routes->post('menus', 'MenuController::create', ['filter' => 'permission:cms.menus.write']);
        // Menu Items CRUD
        $routes->get('menu-items', 'MenuItemController::index', ['filter' => 'permission:cms.menus.read']);
        $routes->post('menu-items', 'MenuItemController::create', ['filter' => 'permission:cms.menus.write']);
        // Pages CRUD
        $routes->get('pages', 'PageController::index', ['filter' => 'permission:cms.pages.read']);
        $routes->get('pages/check-slug', 'PageController::checkSlug', ['filter' => 'permission:cms.pages.read']);
        $routes->post('pages', 'PageController::create', ['filter' => 'permission:cms.pages.write']);
        // Languages CRUD
        $routes->get('languages', 'LanguageController::index', ['filter' => 'permission:cms.languages.read']);
        $routes->post('languages', 'LanguageController::create', ['filter' => 'permission:cms.languages.write']);
        // Settings CRUD
        $routes->get('settings', 'SettingController::index', ['filter' => 'permission:cms.settings.read']);
        $routes->post('settings', 'SettingController::create', ['filter' => 'permission:cms.settings.write']);
        $routes->post('settings/batch', 'SettingController::batch', ['filter' => 'permission:cms.settings.write']);
        // Block Types CRUD
        $routes->get('block-types', 'BlockTypeController::index', ['filter' => 'permission:cms.blocks.read']);
        $routes->get('block-types/templates', 'BlockTypeController::templates', ['filter' => 'permission:cms.blocks.read']);
        $routes->post('block-types', 'BlockTypeController::create', ['filter' => 'permission:cms.blocks.write']);
        // Collections CRUD
        $routes->get('collections', 'CollectionController::index', ['filter' => 'permission:cms.collections.read']);
        $routes->get('collections/check-slug', 'CollectionController::checkSlug', ['filter' => 'permission:cms.collections.read']);
        $routes->post('collections', 'CollectionController::create', ['filter' => 'permission:cms.collections.write']);
        // Entries CRUD
        $routes->get('entries', 'EntryController::index', ['filter' => 'permission:cms.entries.read']);
        $routes->get('entries/check-slug', 'EntryController::checkSlug', ['filter' => 'permission:cms.entries.read']);
        $routes->post('entries', 'EntryController::create', ['filter' => 'permission:cms.entries.write']);
        // Category CRUD
        $routes->get('categories', 'CategoryController::index', ['filter' => 'permission:cms.categories.read']);
        $routes->get('categories/check-slug', 'CategoryController::checkSlug', ['filter' => 'permission:cms.categories.read']);
        $routes->post('categories', 'CategoryController::create', ['filter' => 'permission:cms.categories.write']);
        // Tag CRUD
        $routes->get('tags', 'TagController::index', ['filter' => 'permission:cms.tags.read']);
        $routes->post('tags', 'TagController::create', ['filter' => 'permission:cms.tags.write']);

        // Redirects CRUD
        $routes->get('redirects', 'RedirectController::index', ['filter' => 'permission:cms.redirects.read']);
        $routes->post('redirects', 'RedirectController::create', ['filter' => 'permission:cms.redirects.write']);

        // Translation Auditing
        $routes->get('translations/audit/stats', 'TranslationAuditController::stats', ['filter' => 'permission:cms.languages.read']);
        $routes->get('translations/audit/report', 'TranslationAuditController::report', ['filter' => 'permission:cms.languages.read']);
        $routes->get('translations/audit/resource/(:segment)/(:num)', 'TranslationAuditController::resource/$1/$2', ['filter' => 'permission:cms.languages.read']);
        $routes->get('translations/audit/owner/(:segment)/(:num)', 'TranslationAuditController::owner/$1/$2', ['filter' => 'permission:cms.languages.read']);
        $routes->get('menus/(:num)', 'MenuController::show/$1', ['filter' => 'permission:cms.menus.read']);
        $routes->put('menus/(:num)', 'MenuController::update/$1', ['filter' => 'permission:cms.menus.write']);
        $routes->delete('menus/(:num)', 'MenuController::delete/$1', ['filter' => 'permission:cms.menus.write']);
        $routes->get('menu-items/(:num)', 'MenuItemController::show/$1', ['filter' => 'permission:cms.menus.read']);
        $routes->put('menu-items/(:num)', 'MenuItemController::update/$1', ['filter' => 'permission:cms.menus.write']);
        $routes->delete('menu-items/(:num)', 'MenuItemController::delete/$1', ['filter' => 'permission:cms.menus.write']);
        $routes->get('pages/(:num)', 'PageController::show/$1', ['filter' => 'permission:cms.pages.read']);
        $routes->put('pages/(:num)', 'PageController::update/$1', ['filter' => 'permission:cms.pages.write']);
        $routes->delete('pages/(:num)', 'PageController::delete/$1', ['filter' => 'permission:cms.pages.write']);
        $routes->get('languages/(:num)', 'LanguageController::show/$1', ['filter' => 'permission:cms.languages.read']);
        $routes->put('languages/(:num)', 'LanguageController::update/$1', ['filter' => 'permission:cms.languages.write']);
        $routes->delete('languages/(:num)', 'LanguageController::delete/$1', ['filter' => 'permission:cms.languages.write']);
        $routes->get('settings/(:num)', 'SettingController::show/$1', ['filter' => 'permission:cms.settings.read']);
        $routes->put('settings/(:num)', 'SettingController::update/$1', ['filter' => 'permission:cms.settings.write']);
        $routes->delete('settings/(:num)', 'SettingController::delete/$1', ['filter' => 'permission:cms.settings.write']);
        // Setting Connections
        $routes->get('settings/(:num)/connections', 'SettingConnectionController::index/$1', ['filter' => 'permission:cms.settings.read']);
        $routes->post('settings/(:num)/connections', 'SettingConnectionController::create/$1', ['filter' => 'permission:cms.settings.write']);
        $routes->delete('settings/(:num)/connections/(:num)', 'SettingConnectionController::delete/$1/$2', ['filter' => 'permission:cms.settings.write']);
        // File usages (federated query for Admin "Usado en" panel)
        $routes->get('files/(:num)/usages', 'FileUsageController::usages/$1', ['filter' => 'permission:cms.entries.read']);
        // File Translations CRUD
        $routes->get('files/(:num)/translations', 'FileTranslationController::index/$1', ['filter' => 'permission:cms.pages.read']);
        $routes->get('files/(:num)/translations/(:num)', 'FileTranslationController::show/$1/$2', ['filter' => 'permission:cms.pages.read']);
        $routes->post('files/(:num)/translations', 'FileTranslationController::create/$1', ['filter' => 'permission:cms.pages.write']);
        $routes->put('files/(:num)/translations/(:num)', 'FileTranslationController::update/$1/$2', ['filter' => 'permission:cms.pages.write']);
        $routes->delete('files/(:num)/translations/(:num)', 'FileTranslationController::delete/$1/$2', ['filter' => 'permission:cms.pages.write']);
        $routes->get('block-types/(:num)', 'BlockTypeController::show/$1', ['filter' => 'permission:cms.blocks.read']);
        $routes->get('block-types/(:num)/usages', 'BlockTypeController::usages/$1', ['filter' => 'permission:cms.blocks.read']);
        $routes->put('block-types/(:num)', 'BlockTypeController::update/$1', ['filter' => 'permission:cms.blocks.write']);
        $routes->delete('block-types/(:num)', 'BlockTypeController::delete/$1', ['filter' => 'permission:cms.blocks.write']);
        // Block Instances CRUD nested under pages
        $routes->get('pages/(:num)/blocks', 'BlockInstanceController::indexForPage/$1', ['filter' => 'permission:cms.pages.read']);
        $routes->get('pages/(:num)/blocks/(:num)', 'BlockInstanceController::show/$2', ['filter' => 'permission:cms.pages.read']);
        $routes->post('pages/(:num)/blocks', 'BlockInstanceController::create', ['filter' => 'permission:cms.pages.write']);
        $routes->put('pages/(:num)/blocks/(:num)', 'BlockInstanceController::update/$2', ['filter' => 'permission:cms.pages.write']);
        $routes->delete('pages/(:num)/blocks/(:num)', 'BlockInstanceController::delete/$2', ['filter' => 'permission:cms.pages.write']);
        $routes->get('collections/(:num)', 'CollectionController::show/$1', ['filter' => 'permission:cms.collections.read']);
        $routes->put('collections/(:num)', 'CollectionController::update/$1', ['filter' => 'permission:cms.collections.write']);
        $routes->delete('collections/(:num)', 'CollectionController::delete/$1', ['filter' => 'permission:cms.collections.admin']);
        $routes->get('entries/(:num)', 'EntryController::show/$1', ['filter' => 'permission:cms.entries.read']);
        $routes->put('entries/(:num)', 'EntryController::update/$1', ['filter' => 'permission:cms.entries.write']);
        $routes->delete('entries/(:num)', 'EntryController::delete/$1', ['filter' => 'permission:cms.entries.admin']);
        // Block Instances CRUD nested under entries
        $routes->get('entries/(:num)/blocks', 'BlockInstanceController::indexForEntry/$1', ['filter' => 'permission:cms.pages.read']);
        $routes->get('entries/(:num)/blocks/(:num)', 'BlockInstanceController::show/$2', ['filter' => 'permission:cms.pages.read']);
        $routes->post('entries/(:num)/blocks', 'BlockInstanceController::create', ['filter' => 'permission:cms.pages.write']);
        $routes->put('entries/(:num)/blocks/(:num)', 'BlockInstanceController::update/$2', ['filter' => 'permission:cms.pages.write']);
        $routes->delete('entries/(:num)/blocks/(:num)', 'BlockInstanceController::delete/$2', ['filter' => 'permission:cms.pages.write']);
        $routes->get('categories/(:num)', 'CategoryController::show/$1', ['filter' => 'permission:cms.categories.read']);
        $routes->put('categories/(:num)', 'CategoryController::update/$1', ['filter' => 'permission:cms.categories.write']);
        $routes->delete('categories/(:num)', 'CategoryController::delete/$1', ['filter' => 'permission:cms.categories.write']);
        $routes->get('tags/(:num)', 'TagController::show/$1', ['filter' => 'permission:cms.tags.read']);
        $routes->put('tags/(:num)', 'TagController::update/$1', ['filter' => 'permission:cms.tags.write']);
        $routes->delete('tags/(:num)', 'TagController::delete/$1', ['filter' => 'permission:cms.tags.write']);

        $routes->get('redirects/(:num)', 'RedirectController::show/$1', ['filter' => 'permission:cms.redirects.read']);
        $routes->put('redirects/(:num)', 'RedirectController::update/$1', ['filter' => 'permission:cms.redirects.write']);
        $routes->delete('redirects/(:num)', 'RedirectController::delete/$1', ['filter' => 'permission:cms.redirects.admin']);

        // Entry Pivot relations
        $routes->post('entries/(:num)/taxonomy', 'EntryController::syncTaxonomy/$1', ['filter' => 'permission:cms.entries.write']);
        $routes->post('entries/(:num)/categories', 'EntryController::setCategories/$1', ['filter' => 'permission:cms.entries.write']);
        $routes->post('entries/(:num)/tags', 'EntryController::setTags/$1', ['filter' => 'permission:cms.entries.write']);

        // Form Submissions (admin)
        $routes->get('submissions', 'FormSubmissionController::index', ['filter' => 'permission:cms.submissions.read']);
        $routes->get('submissions/counts', 'FormSubmissionController::counts', ['filter' => 'permission:cms.submissions.read']);
        $routes->get('submissions/(:num)', 'FormSubmissionController::show/$1', ['filter' => 'permission:cms.submissions.read']);
        $routes->patch('submissions/(:num)/status', 'FormSubmissionController::updateStatus/$1', ['filter' => 'permission:cms.submissions.write']);

        // Forms (admin — dynamic form builder)
        $routes->get('forms', 'FormController::index', ['filter' => 'permission:cms.forms.read']);
        $routes->post('forms', 'FormController::store', ['filter' => 'permission:cms.forms.write']);
        $routes->get('forms/(:num)', 'FormController::show/$1', ['filter' => 'permission:cms.forms.read']);
        $routes->put('forms/(:num)', 'FormController::update/$1', ['filter' => 'permission:cms.forms.write']);
        $routes->delete('forms/(:num)', 'FormController::destroy/$1', ['filter' => 'permission:cms.forms.admin']);
        $routes->get('forms/(:num)/fields', 'FormController::fields/$1', ['filter' => 'permission:cms.forms.read']);
        $routes->post('forms/(:num)/fields', 'FormController::storeField/$1', ['filter' => 'permission:cms.forms.write']);
        $routes->put('forms/(:num)/fields/(:num)', 'FormController::updateField/$1/$2', ['filter' => 'permission:cms.forms.write']);
        $routes->delete('forms/(:num)/fields/(:num)', 'FormController::destroyField/$1/$2', ['filter' => 'permission:cms.forms.write']);
        $routes->patch('forms/(:num)/fields/reorder', 'FormController::reorderFields/$1', ['filter' => 'permission:cms.forms.write']);

        // Analytics (admin — website visit statistics)
        $routes->get('analytics/overview', 'AnalyticsController::overview', ['filter' => 'permission:cms.analytics.read']);
        $routes->get('analytics/pages', 'AnalyticsController::pages', ['filter' => 'permission:cms.analytics.read']);
        $routes->get('analytics/referrers', 'AnalyticsController::referrers', ['filter' => 'permission:cms.analytics.read']);
        $routes->get('analytics/devices', 'AnalyticsController::devices', ['filter' => 'permission:cms.analytics.read']);
        $routes->get('analytics/timeseries', 'AnalyticsController::timeseries', ['filter' => 'permission:cms.analytics.read']);
    });
});

// Public endpoints — all require X-App-Key (webappkey) + throttle
$routes->post('public/submissions', '\App\Controllers\Api\V1\Cms\PublicFormSubmissionController::store', ['filter' => ['webappkey', 'throttle']]);
$routes->post('public/track', '\App\Controllers\Api\V1\Cms\PublicTrackingController::track', ['filter' => ['webappkey', 'throttle']]);
$routes->get('public/settings', '\App\Controllers\Api\V1\Cms\PublicSettingController::index', ['filter' => ['webappkey', 'throttle']]);
$routes->get('public/(:segment)/pages', '\App\Controllers\Api\V1\Cms\PublicPageController::index/$1', ['filter' => ['webappkey', 'throttle']]);
$routes->get('public/(:segment)/pages/(.+)', '\App\Controllers\Api\V1\Cms\PublicPageController::show/$1/$2', ['filter' => ['webappkey', 'throttle']]);
$routes->get('public/menus/(:segment)', '\App\Controllers\Api\V1\Cms\PublicMenuController::show/$1', ['filter' => ['webappkey', 'throttle']]);
$routes->get('public/(:segment)/collections', '\App\Controllers\Api\V1\Cms\PublicCollectionController::index/$1', ['filter' => ['webappkey', 'throttle']]);
$routes->get('public/(:segment)/categories/(:segment)', '\App\Controllers\Api\V1\Cms\PublicCategoryController::index/$1/$2', ['filter' => ['webappkey', 'throttle']]);
$routes->get('public/(:segment)/tags/(:segment)', '\App\Controllers\Api\V1\Cms\PublicTagController::index/$1/$2', ['filter' => ['webappkey', 'throttle']]);
$routes->get('public/(:segment)/entries/(:segment)', '\App\Controllers\Api\V1\Cms\PublicEntryController::index/$1/$2', ['filter' => ['webappkey', 'throttle']]);
$routes->get('public/(:segment)/entries/(:segment)/(.+)', '\App\Controllers\Api\V1\Cms\PublicEntryController::show/$1/$2/$3', ['filter' => ['webappkey', 'throttle']]);
$routes->get('public/redirects/(.*)', '\App\Controllers\Api\V1\Cms\PublicRedirectController::resolve/$1', ['filter' => ['webappkey', 'throttle']]);
// Public form definition (for web rendering dynamic forms)
$routes->get('public/(:segment)/forms/(:segment)', '\App\Controllers\Api\V1\Cms\PublicFormController::definition/$1/$2', ['filter' => ['webappkey', 'throttle']]);
