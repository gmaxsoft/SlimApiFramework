<?php

declare(strict_types=1);

namespace App\Core\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Nadaje unikalny identyfikator żądania do śledzenia (logi, korelacja).
 * Przyjmuje nagłówek `X-Request-ID` od klienta lub generuje identyfikator w stylu UUID v4.
 */
final class RequestIdMiddleware implements MiddlewareInterface
{
    /** Nazwa atrybutu żądania PSR-7 przechowującego identyfikator. */
    public const ATTRIBUTE = 'request_id';

    /**
     * Wzbogaca żądanie o `request_id` i dodaje ten sam identyfikator do odpowiedzi w nagłówku `X-Request-ID`.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $requestId = $request->getHeaderLine('X-Request-ID');
        if ($requestId === '') {
            $requestId = sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                random_int(0, 0xffff),
                random_int(0, 0xffff),
                random_int(0, 0xffff),
                random_int(0, 0x0fff) | 0x4000,
                random_int(0, 0x3fff) | 0x8000,
                random_int(0, 0xffff),
                random_int(0, 0xffff),
                random_int(0, 0xffff),
            );
        }

        $request = $request->withAttribute(self::ATTRIBUTE, $requestId);
        $response = $handler->handle($request);

        return $response->withHeader('X-Request-ID', $requestId);
    }
}
