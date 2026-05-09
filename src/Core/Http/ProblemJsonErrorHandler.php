<?php

declare(strict_types=1);

namespace App\Core\Http;

use JimTools\JwtAuth\Exceptions\AuthorizationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpException as SlimHttpException;
use Slim\Interfaces\ErrorHandlerInterface;
use Throwable;

/**
 * Obsługa błędów Slim zwracająca odpowiedzi JSON Problem Details (RFC 7807).
 * Wyjątki są logowane zgodnie z żądaniem Slim (`$logErrors`, `$logErrorDetails`).
 */
final class ProblemJsonErrorHandler implements ErrorHandlerInterface
{
    /**
     * @param LoggerInterface $logger Logger Monolog do zapisu błędów serwera
     */
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Buduje odpowiedź HTTP z treścią problemu na podstawie wyjątku i ustawień wyświetlania szczegółów.
     *
     * @param ServerRequestInterface $request              Bieżące żądanie PSR-7
     * @param Throwable              $exception            Rzucony wyjątek
     * @param bool                   $displayErrorDetails Czy pokazywać komunikat wyjątku użytkownikowi (np. tryb debug)
     * @param bool                   $logErrors           Czy zapisać błąd w logu
     * @param bool                   $logErrorDetails     Czy dołączyć pełny stack trace do wpisu logu
     */
    public function __invoke(
        ServerRequestInterface $request,
        Throwable $exception,
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails,
    ): ResponseInterface {
        $status = 500;
        $title = 'Internal Server Error';
        $detail = 'An unexpected error occurred.';

        if ($exception instanceof AuthorizationException) {
            $status = 401;
            $title = 'Unauthorized';
            $detail = $exception->getMessage();
        } elseif ($exception instanceof SlimHttpException) {
            $status = $exception->getCode() >= 400 && $exception->getCode() < 600 ? $exception->getCode() : 500;
            $title = $exception->getTitle() !== '' ? $exception->getTitle() : 'HTTP Error';
            $detail = $exception->getDescription() !== '' ? $exception->getDescription() : $exception->getMessage();
        } elseif ($exception->getCode() >= 400 && $exception->getCode() < 600) {
            $status = $exception->getCode();
            $detail = $exception->getMessage();
        }

        if ($displayErrorDetails && $status >= 500) {
            $detail = $exception->getMessage();
        }

        if ($logErrors && !($exception instanceof AuthorizationException)) {
            $this->logger->error($exception->getMessage(), [
                'exception' => $exception::class,
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $logErrorDetails ? $exception->getTraceAsString() : null,
            ]);
        }

        $problem = new ProblemDetails(
            title: $title,
            status: $status,
            detail: $detail,
            instance: $request->getUri()->getPath(),
        );

        $response = new \Nyholm\Psr7\Response($status);
        $response = $response->withHeader('Content-Type', 'application/problem+json; charset=utf-8');

        $body = json_encode($problem->toArray(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $response->getBody()->write($body);

        return $response;
    }
}
