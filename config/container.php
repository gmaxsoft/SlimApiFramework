<?php

declare(strict_types=1);

use App\Core\Controllers\AuthController;
use App\Core\Database\PdoFactory;
use App\Core\Http\ProblemJsonErrorHandler;
use App\Core\Middleware\HttpLoggingMiddleware;
use App\Core\Middleware\RateLimitMiddleware;
use App\Core\Logging\RequestIdProcessor;
use App\Modules\User\Repositories\UserRepository;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use JimTools\JwtAuth\Decoder\FirebaseDecoder;
use JimTools\JwtAuth\Middleware\JwtAuthentication;
use JimTools\JwtAuth\Options;
use JimTools\JwtAuth\Secret;
use function DI\create;
use function DI\factory;
use function DI\get;

/**
 * Definicje kontenera PHP-DI (PSR-11). Moduły pozostają odizolowane; Core spina wspólną infrastrukturę.
 *
 * @return array<string, mixed>
 */
return [
    /** Ładuje tablicę ustawień z pliku settings.php (wartości już po wczytaniu .env). */
    'settings' => factory(static fn (): array => require __DIR__ . '/settings.php'),

    /** Tworzy połączenie PDO z MySQL według sekcji `db` w ustawieniach. */
    \PDO::class => factory(static function (ContainerInterface $c): \PDO {
        return (new PdoFactory())->create($c->get('settings')['db']);
    }),

    /** Konfiguruje Monolog: strumień do pliku, poziom zależny od trybu debug, procesor z identyfikatorem żądania. */
    LoggerInterface::class => factory(static function (ContainerInterface $c): LoggerInterface {
        /** @var array<string, mixed> $settings */
        $settings = $c->get('settings');
        /** @var string $path */
        $path = $settings['logging']['path'];
        $logger = new Logger('app');
        $logger->pushProcessor(new RequestIdProcessor());
        $level = ($settings['app']['debug'] ?? false) ? Level::Debug : Level::Info;
        $logger->pushHandler(new StreamHandler($path, $level));

        return $logger;
    }),

    /** Domyślny handler błędów Slim zwracający JSON Problem Details (RFC 7807). */
    ProblemJsonErrorHandler::class => create()->constructor(get(LoggerInterface::class)),

    /** Middleware JWT (jimtools/jwt-auth, firebase/php-jwt ^7): roszczenia w atrybucie żądania `jwt`. */
    JwtAuthentication::class => factory(static function (ContainerInterface $c): JwtAuthentication {
        /** @var array<string, mixed> $settings */
        $settings = $c->get('settings');
        /** @var array<string, mixed> $jwt */
        $jwt = $settings['jwt'];
        $env = (string) ($settings['app']['env'] ?? 'production');
        $isProduction = $env === 'production';

        $secret = new Secret(
            (string) ($jwt['secret'] ?? ''),
            (string) ($jwt['algorithm'] ?? 'HS256'),
        );
        $decoder = new FirebaseDecoder($secret);

        $options = new Options(
            isSecure: $isProduction,
            attribute: 'jwt',
        );

        return new JwtAuthentication($options, $decoder);
    }),

    /** Ogranicza liczbę żądań na adres IP w oknie czasowym (tabela `rate_limit_hits`). */
    RateLimitMiddleware::class => factory(static function (ContainerInterface $c): RateLimitMiddleware {
        /** @var array<string, mixed> $settings */
        $settings = $c->get('settings');
        $rl = $settings['rate_limit'];

        return new RateLimitMiddleware(
            $c->get(\PDO::class),
            (int) ($rl['max_requests'] ?? 100),
            (int) ($rl['window_seconds'] ?? 60),
        );
    }),

    /** Loguje podsumowanie żądania HTTP po zakończeniu obsługi (metoda, ścieżka, kod statusu). */
    HttpLoggingMiddleware::class => create()->constructor(get(LoggerInterface::class)),

    /** Kontroler logowania z ręcznym podaniem ustawień JWT z konfiguracji. */
    AuthController::class => factory(static function (ContainerInterface $c): AuthController {
        /** @var array<string, mixed> $settings */
        $settings = $c->get('settings');

        return new AuthController(
            $c->get(UserRepository::class),
            $settings['jwt'],
        );
    }),

    /** Repozytorium użytkowników modułu User (wstrzyknięte PDO z kontenera). */
    UserRepository::class => create()->constructor(get(\PDO::class)),
];
