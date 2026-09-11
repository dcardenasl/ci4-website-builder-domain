<?php

declare(strict_types=1);

/** @var \CodeIgniter\Router\RouteCollection $routes */

// Hub-initiated calls only — signed with hub.internalSecret. They are not
// public Web app routes and must never be reachable with a user JWT alone.
$routes->group('internal', ['filter' => ['hubsignature', 'throttle']], function ($routes): void {
    $routes->get('files/(:num)/usage', '\App\Controllers\Api\V1\Internal\InternalFileController::usage/$1');
    $routes->post('files/(:num)/invalidate-cache', '\App\Controllers\Api\V1\Internal\InternalFileController::invalidateCache/$1');
});
