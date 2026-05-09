<?php

declare(strict_types=1);

/**
 * Trasy modułu User — rejestrowane w bootstrapie aplikacji z prefiksem wersjonowania URL `/v1`.
 * Oddzielny plik tras na moduł utrzymuje granice modularnego monolitu.
 */

use App\Core\Middleware\RateLimitMiddleware;
use App\Modules\User\Controllers\UserController;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use Tuupola\Middleware\JwtAuthentication;

/**
 * Rejestruje endpointy REST użytkownika oraz middleware JWT dla trasy `/me` i limitowania dla całej grupy.
 */
return static function (App $app): void {
    $app->group('/v1/users', function (RouteCollectorProxy $group): void {
        $group->get('', [UserController::class, 'index']);
        $group->post('', [UserController::class, 'create']);
        $group->get('/{id:[0-9]+}', [UserController::class, 'show']);
        $group->get('/me', [UserController::class, 'me'])->add(JwtAuthentication::class);
    })->add(RateLimitMiddleware::class);
};
