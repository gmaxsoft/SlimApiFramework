<?php

declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\Http\ProblemDetails;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Proste ograniczanie częstotliwości żądań na podstawie adresu IP klienta z użyciem bazy MySQL.
 * Tabela `rate_limit_hits` jest tworzona w pliku `database/schema.sql`.
 */
final class RateLimitMiddleware implements MiddlewareInterface
{
    /**
     * @param PDO $pdo              Połączenie PDO do zapisu liczników
     * @param int $maxRequests      Maksymalna liczba żądań dozwolona w jednym oknie
     * @param int $windowSeconds    Długość okna czasowego w sekundach
     */
    public function __construct(
        private readonly PDO $pdo,
        private readonly int $maxRequests,
        private readonly int $windowSeconds,
    ) {
    }

    /**
     * Inkrementuje licznik dla klienta w bieżącym oknie; przy przekroczeniu limitu zwraca 429 (Problem Details).
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $serverParams = $request->getServerParams();
        $ip = (string) ($serverParams['REMOTE_ADDR'] ?? '0.0.0.0');
        $now = time();
        $windowStart = $now - ($now % $this->windowSeconds);

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO rate_limit_hits (client_key, window_start, hit_count)
                 VALUES (:key, :ws, 1)
                 ON DUPLICATE KEY UPDATE hit_count = hit_count + 1',
            );
            $stmt->execute([
                'key' => $ip,
                'ws' => $windowStart,
            ]);

            $sel = $this->pdo->prepare(
                'SELECT hit_count FROM rate_limit_hits WHERE client_key = :key AND window_start = :ws LIMIT 1',
            );
            $sel->execute(['key' => $ip, 'ws' => $windowStart]);
            $row = $sel->fetch(PDO::FETCH_ASSOC);
            $hits = (int) ($row['hit_count'] ?? 0);
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        if ($hits > $this->maxRequests) {
            $problem = new ProblemDetails(
                title: 'Too Many Requests',
                status: 429,
                detail: 'Rate limit exceeded for this client.',
                instance: $request->getUri()->getPath(),
            );

            $response = new \Nyholm\Psr7\Response(429);
            $response = $response
                ->withHeader('Content-Type', 'application/problem+json; charset=utf-8')
                ->withHeader('Retry-After', (string) $this->windowSeconds);
            $response->getBody()->write(json_encode($problem->toArray(), JSON_THROW_ON_ERROR));

            return $response;
        }

        return $handler->handle($request);
    }
}
