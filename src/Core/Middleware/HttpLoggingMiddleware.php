<?php

declare(strict_types=1);

namespace App\Core\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * Loguje podstawowe informacje o żądaniu HTTP po jego obsłużeniu (metoda, ścieżka, kod statusu odpowiedzi).
 * Współpracuje z procesorem Monolog dodającym `request_id`.
 */
final class HttpLoggingMiddleware implements MiddlewareInterface
{
    /**
     * @param LoggerInterface $logger Logger aplikacji (Monolog)
     */
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Przekazuje żądanie dalej, a następnie zapisuje jeden wpis kanału `http.access` z metadanymi odpowiedzi.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        $this->logger->info('http.access', [
            'method' => $request->getMethod(),
            'path' => $request->getUri()->getPath(),
            'status' => $response->getStatusCode(),
        ]);

        return $response;
    }
}
