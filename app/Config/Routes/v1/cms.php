<?php

declare (strict_types=1);
/** @var \CodeIgniter\Router\RouteCollection $routes */
$routes->group('cms', ['namespace' => '\App\Controllers\Api\V1\Cms'], function ($routes): void {
    // Auth & Admin Protected Group
    $routes->group('', ['filter' => ['domainauth', 'throttle']], function ($routes): void {
        // Pages CRUD
        $routes->get('pages', 'PageController::index', ['filter' => 'permission:cms.pages.read']);
        $routes->get('pages/(:num)', 'PageController::show/$1', ['filter' => 'permission:cms.pages.read']);
        $routes->post('pages', 'PageController::create', ['filter' => 'permission:cms.pages.write']);
        $routes->put('pages/(:num)', 'PageController::update/$1', ['filter' => 'permission:cms.pages.write']);
        $routes->delete('pages/(:num)', 'PageController::delete/$1', ['filter' => 'permission:cms.pages.write']);

        // Languages CRUD
        $routes->get('languages', 'LanguageController::index', ['filter' => 'permission:cms.languages.read']);
        $routes->post('languages', 'LanguageController::create', ['filter' => 'permission:cms.languages.write']);
        // Settings CRUD
        $routes->get('settings', 'SettingController::index', ['filter' => 'permission:cms.settings.read']);
        $routes->post('settings', 'SettingController::create', ['filter' => 'permission:cms.settings.write']);
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
    });
});

// Public endpoints
$routes->get('public/(:segment)/pages/(.+)', '\App\Controllers\Api\V1\Cms\PublicPageController::show/$1/$2', ['filter' => 'throttle']);
