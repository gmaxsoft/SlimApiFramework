<?php

declare(strict_types=1);

use App\Core\Controllers\AuthController;
use App\Core\Controllers\DocsController;
use App\Core\Controllers\HealthController;
use App\Core\Middleware\RateLimitMiddleware;
use Slim\App;

/**
 * Mapa tras aplikacji: endpointy Core oraz dołączone pliki tras modułów.
 */
return static function (App $app): void {
    $app->get('/docs', [DocsController::class, 'swaggerUi']);
    $app->get('/openapi.json', [DocsController::class, 'openApiJson']);

    $app->get('/v1/health', [HealthController::class, 'check']);
    $app->post('/v1/auth/login', [AuthController::class, 'login'])
        ->add(RateLimitMiddleware::class);

    (require __DIR__ . '/../src/Modules/User/routes.php')($app);
};
