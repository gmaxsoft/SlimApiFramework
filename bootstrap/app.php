<?php

declare(strict_types=1);

/**
 * Bootstrap aplikacji: wczytuje zmienne środowiskowe, buduje kontener PSR-11 i zwraca skonfigurowaną aplikację Slim.
 *
 * Zasada modularnego monolitu: warstwa Core wiąże infrastrukturę (DI, stos HTTP, aspekty poprzeczne);
 * każdy moduł w `src/Modules/*` ma własne trasy, kontrolery, serwisy, repozytoria i modele.
 */

use DI\Bridge\Slim\Bridge;
use DI\ContainerBuilder;
use Nyholm\Psr7\Factory\Psr17Factory;
use Slim\Factory\AppFactory;

require dirname(__DIR__) . '/vendor/autoload.php';

$projectRoot = dirname(__DIR__);
if (is_readable($projectRoot . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable($projectRoot);
    $dotenv->safeLoad();
}

$containerBuilder = new ContainerBuilder();
$containerBuilder->useAutowiring(true);
$containerBuilder->addDefinitions($projectRoot . '/config/container.php');
$container = $containerBuilder->build();

$psr17 = new Psr17Factory();
AppFactory::setResponseFactory($psr17);
AppFactory::setStreamFactory($psr17);

/** @var \Slim\App $app Integracja PHP-DI ze Slim umożliwia wstrzykiwanie zależności i segmentów trasy do akcji kontrolerów. */
$app = Bridge::create($container);

(require $projectRoot . '/config/middleware.php')($app, $container);
(require $projectRoot . '/config/routes.php')($app);

return $app;
