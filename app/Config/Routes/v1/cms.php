<?php

declare (strict_types=1);
/** @var \CodeIgniter\Router\RouteCollection $routes */
$routes->group('cms', ['namespace' => '\App\Controllers\Api\V1\Cms'], function ($routes): void {
    // Auth & Admin Protected Group
    $routes->group('', ['filter' => ['domainauth', 'throttle']], function ($routes): void {
        // Menus CRUD
        $routes->get('menus', 'MenuController::index', ['filter' => 'permission:cms.menus.read']);
        $routes->post('menus', 'MenuController::create', ['filter' => 'permission:cms.menus.write']);
        // Menu Items CRUD
        $routes->get('menu-items', 'MenuItemController::index', ['filter' => 'permission:cms.menus.read']);
        $routes->post('menu-items', 'MenuItemController::create', ['filter' => 'permission:cms.menus.write']);
        // Pages CRUD
        $routes->get('pages', 'PageController::index', ['filter' => 'permission:cms.pages.read']);
        $routes->post('pages', 'PageController::create', ['filter' => 'permission:cms.pages.write']);
        // Languages CRUD
        $routes->get('languages', 'LanguageController::index', ['filter' => 'permission:cms.languages.read']);
        $routes->post('languages', 'LanguageController::create', ['filter' => 'permission:cms.languages.write']);
        // Settings CRUD
        $routes->get('settings', 'SettingController::index', ['filter' => 'permission:cms.settings.read']);
        $routes->post('settings', 'SettingController::create', ['filter' => 'permission:cms.settings.write']);
        // Block Types CRUD
        $routes->get('block-types', 'BlockTypeController::index', ['filter' => 'permission:cms.blocks.read']);
        $routes->post('block-types', 'BlockTypeController::create', ['filter' => 'permission:cms.blocks.write']);
        // Collections CRUD
        $routes->get('collections', 'CollectionController::index', ['filter' => 'permission:cms.collections.read']);
        $routes->post('collections', 'CollectionController::create', ['filter' => 'permission:cms.collections.write']);
        // Entries CRUD
        $routes->get('entries', 'EntryController::index', ['filter' => 'permission:cms.entries.read']);
        $routes->post('entries', 'EntryController::create', ['filter' => 'permission:cms.entries.write']);
        // Category CRUD
        $routes->get('categories', 'CategoryController::index', ['filter' => 'permission:cms.categories.read']);
        $routes->post('categories', 'CategoryController::create', ['filter' => 'permission:cms.categories.write']);
        // Tag CRUD
        $routes->get('tags', 'TagController::index', ['filter' => 'permission:cms.tags.read']);
        $routes->post('tags', 'TagController::create', ['filter' => 'permission:cms.tags.write']);

        // Redirects CRUD
        $routes->get('redirects', 'RedirectController::index', ['filter' => 'permission:cms.redirects.read']);
        $routes->post('redirects', 'RedirectController::create', ['filter' => 'permission:cms.redirects.write']);
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
        // File Translations CRUD
        $routes->get('files/(:num)/translations', 'FileTranslationController::index/$1', ['filter' => 'permission:cms.pages.read']);
        $routes->get('files/(:num)/translations/(:num)', 'FileTranslationController::show/$1/$2', ['filter' => 'permission:cms.pages.read']);
        $routes->post('files/(:num)/translations', 'FileTranslationController::create/$1', ['filter' => 'permission:cms.pages.write']);
        $routes->put('files/(:num)/translations/(:num)', 'FileTranslationController::update/$1/$2', ['filter' => 'permission:cms.pages.write']);
        $routes->delete('files/(:num)/translations/(:num)', 'FileTranslationController::delete/$1/$2', ['filter' => 'permission:cms.pages.write']);
        $routes->get('block-types/(:num)', 'BlockTypeController::show/$1', ['filter' => 'permission:cms.blocks.read']);
        $routes->put('block-types/(:num)', 'BlockTypeController::update/$1', ['filter' => 'permission:cms.blocks.write']);
        $routes->delete('block-types/(:num)', 'BlockTypeController::delete/$1', ['filter' => 'permission:cms.blocks.write']);
        // Block Instances CRUD nested under pages
        $routes->get('pages/(:num)/blocks', 'BlockInstanceController::index', ['filter' => 'permission:cms.pages.read']);
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
        $routes->get('entries/(:num)/blocks', 'BlockInstanceController::index', ['filter' => 'permission:cms.pages.read']);
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
        $routes->post('entries/(:num)/categories', 'EntryController::setCategories/$1', ['filter' => 'permission:cms.entries.write']);
        $routes->post('entries/(:num)/tags', 'EntryController::setTags/$1', ['filter' => 'permission:cms.entries.write']);
    });
});
// Public endpoints
$routes->get('public/settings', '\App\Controllers\Api\V1\Cms\PublicSettingController::index', ['filter' => 'throttle']);
$routes->get('public/(:segment)/pages', '\App\Controllers\Api\V1\Cms\PublicPageController::index/$1', ['filter' => 'throttle']);
$routes->get('public/(:segment)/pages/(.+)', '\App\Controllers\Api\V1\Cms\PublicPageController::show/$1/$2', ['filter' => 'throttle']);
$routes->get('public/menus/(:segment)', '\App\Controllers\Api\V1\Cms\PublicMenuController::show/$1', ['filter' => 'throttle']);
$routes->get('public/(:segment)/collections', '\App\Controllers\Api\V1\Cms\PublicCollectionController::index/$1', ['filter' => 'throttle']);
$routes->get('public/(:segment)/entries/(:segment)', '\App\Controllers\Api\V1\Cms\PublicEntryController::index/$1/$2', ['filter' => 'throttle']);
$routes->get('public/(:segment)/entries/(:segment)/(.+)', '\App\Controllers\Api\V1\Cms\PublicEntryController::show/$1/$2/$3', ['filter' => 'throttle']);
$routes->get('public/redirects/(.*)', '\App\Controllers\Api\V1\Cms\PublicRedirectController::resolve/$1', ['filter' => 'throttle']);
