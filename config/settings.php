<?php

declare(strict_types=1);

/**
 * Centralna konfiguracja modularnego monolitu.
 * Wartości pochodzą ze zmiennych środowiska; sekretów nie umieszczaj w repozytorium.
 */
return [
    'app' => [
        'env' => $_ENV['APP_ENV'] ?? 'production',
        'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    ],
    'db' => [
        'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
        'port' => (int) ($_ENV['DB_PORT'] ?? 3306),
        'name' => $_ENV['DB_NAME'] ?? 'slim_api',
        'user' => $_ENV['DB_USER'] ?? 'root',
        'password' => $_ENV['DB_PASSWORD'] ?? '',
        'charset' => 'utf8mb4',
    ],
    'logging' => [
        'path' => dirname(__DIR__) . '/' . ($_ENV['LOG_PATH'] ?? 'logs/app.log'),
    ],
    'jwt' => [
        'secret' => $_ENV['JWT_SECRET'] ?? '',
        'issuer' => $_ENV['JWT_ISSUER'] ?? '',
        'audience' => $_ENV['JWT_AUDIENCE'] ?? '',
        'algorithm' => 'HS256',
    ],
    'rate_limit' => [
        'max_requests' => (int) ($_ENV['RATE_LIMIT_MAX'] ?? 100),
        'window_seconds' => (int) ($_ENV['RATE_LIMIT_WINDOW_SECONDS'] ?? 60),
    ],
];
