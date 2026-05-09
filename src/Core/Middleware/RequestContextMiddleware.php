<?php

declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\Logging\RequestContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Przenosi identyfikator żądania z atrybutów PSR-7 do `RequestContext`, aby procesory Monolog mogły go dopisywać do logów.
 * Powinno działać zaraz po `RequestIdMiddleware` (zgodnie z kolejnością na stosie).
 */
final class RequestContextMiddleware implements MiddlewareInterface
{
    /**
     * Ustawia kontekst logowania na czas obsługi żądania i czyści go w bloku `finally`.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $rid = $request->getAttribute(RequestIdMiddleware::ATTRIBUTE);
        RequestContext::setRequestId(is_string($rid) ? $rid : null);
        try {
            return $handler->handle($request);
        } finally {
            RequestContext::reset();
        }
    }
}
