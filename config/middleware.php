<?php

declare(strict_types=1);

use App\Core\Http\ProblemJsonErrorHandler;
use App\Core\Middleware\HttpLoggingMiddleware;
use App\Core\Middleware\RequestContextMiddleware;
use App\Core\Middleware\RequestIdMiddleware;
use Psr\Container\ContainerInterface;
use Slim\App;

/**
 * Konfiguruje globalny stos middleware Slim (PSR-15).
 * Kolejność „cebuli”: ostatnio dodane przez `$app->add()` wykonuje się pierwsze przy przychodzącym żądaniu.
 *
 * Limitowanie częstotliwości jest podpięte pod wybrane grupy tras (patrz `config/routes.php` i `routes.php` modułów),
 * aby lekkie endpointy jak `/v1/health` nie wymagały połączenia z bazą przy rozwiązywaniu middleware.
 */
return static function (App $app, ContainerInterface $container): void {
    /** @var array<string, mixed> $settings */
    $settings = $container->get('settings');
    $displayDetails = (bool) ($settings['app']['debug'] ?? false);

    $app->addRoutingMiddleware();
    $app->addBodyParsingMiddleware();

    $errorMiddleware = $app->addErrorMiddleware($displayDetails, true, true);
    $errorMiddleware->setDefaultErrorHandler($container->get(ProblemJsonErrorHandler::class));

    $app->add(HttpLoggingMiddleware::class);
    $app->add(RequestContextMiddleware::class);
    $app->add(RequestIdMiddleware::class);
};
