<?php

declare(strict_types=1);

namespace App\Core\Controllers;

use Nyholm\Psr7\Response;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Prosty endpoint „żywotności” aplikacji do sond monitorujących i bilansera obciążenia.
 */
final class HealthController
{
    #[OA\Get(
        path: '/v1/health',
        summary: 'Liveness probe',
        tags: ['System'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'ok'),
                    ],
                ),
            ),
        ],
    )]
    /**
     * Zwraca statyczny JSON potwierdzający, że proces PHP i routing działają poprawnie.
     *
     * @param array<string, string> $args Parametry trasy (puste)
     */
    public function check(ServerRequestInterface $request, ResponseInterface $response, array $args = []): ResponseInterface
    {
        $response = new Response(200);
        $response->getBody()->write(json_encode(['status' => 'ok'], JSON_THROW_ON_ERROR));

        return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}
