<?php

declare(strict_types=1);

/**
 * Front kontroler HTTP: autoload Composer, budowa żądania PSR-7 z superglobals, uruchomienie aplikacji Slim.
 */

require dirname(__DIR__) . '/vendor/autoload.php';

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;

$psr17 = new Psr17Factory();
$creator = new ServerRequestCreator($psr17, $psr17, $psr17, $psr17);
$request = $creator->fromGlobals();

$app = require dirname(__DIR__) . '/bootstrap/app.php';
$app->run($request);
