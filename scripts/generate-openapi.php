<?php

declare(strict_types=1);

/**
 * Skrypt CLI: generuje plik `public/openapi.json` na podstawie atrybutów OpenAPI w katalogu `src`.
 * Uruchom po zmianach w kontrolerach: `composer openapi:generate`.
 */

/**
 * Skanuje źródła i zapisuje wygenerowaną specyfikację OpenAPI do katalogu public.
 */
function main(): void
{
    $root = dirname(__DIR__);
    require $root . '/vendor/autoload.php';

    if (is_readable($root . '/.env')) {
        $dotenv = Dotenv\Dotenv::createImmutable($root);
        $dotenv->safeLoad();
    }

    $openapi = OpenApi\Generator::scan([$root . '/src']);
    $target = $root . '/public/openapi.json';
    file_put_contents($target, $openapi->toJson());

    echo 'Zapisano plik ' . $target . PHP_EOL;
}

main();
