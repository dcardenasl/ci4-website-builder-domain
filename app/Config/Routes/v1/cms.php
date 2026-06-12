<?php

declare(strict_types=1);

/** @var \CodeIgniter\Router\RouteCollection $routes */

$routes->group('cms', ['namespace' => '\App\Controllers\Api\V1\Cms'], function ($routes): void {

    // Auth & Admin Protected Group
    $routes->group('', ['filter' => ['domainauth', 'throttle']], function ($routes): void {

        // Languages CRUD
        $routes->get('languages', 'LanguageController::index', ['filter' => 'permission:cms.languages.read']);
        $routes->get('languages/(:num)', 'LanguageController::show/$1', ['filter' => 'permission:cms.languages.read']);
        $routes->post('languages', 'LanguageController::create', ['filter' => 'permission:cms.languages.write']);
        $routes->put('languages/(:num)', 'LanguageController::update/$1', ['filter' => 'permission:cms.languages.write']);
        $routes->delete('languages/(:num)', 'LanguageController::delete/$1', ['filter' => 'permission:cms.languages.write']);

        // Settings CRUD
        $routes->get('settings', 'SettingController::index', ['filter' => 'permission:cms.settings.read']);
        $routes->get('settings/(:num)', 'SettingController::show/$1', ['filter' => 'permission:cms.settings.read']);
        $routes->post('settings', 'SettingController::create', ['filter' => 'permission:cms.settings.write']);
        $routes->put('settings/(:num)', 'SettingController::update/$1', ['filter' => 'permission:cms.settings.write']);
        $routes->delete('settings/(:num)', 'SettingController::delete/$1', ['filter' => 'permission:cms.settings.write']);
    });
});
