<?php

declare(strict_types=1);

namespace App\Core\Controllers;

use App\Modules\User\Repositories\UserRepository;
use Firebase\JWT\JWT;
use Nyholm\Psr7\Response;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Wymiana poświadczeń na token JWT używany na chronionych trasach.
 * W produkcji należy podłączyć własny IdP lub MFA — tutaj celowo uproszczony przepływ.
 */
final class AuthController
{
    /**
     * @param UserRepository         $users       Repozytorium użytkowników (weryfikacja hasła)
     * @param array<string, mixed>   $jwtSettings Sekcja `jwt` z konfiguracji (secret, iss, aud, algorytm)
     */
    public function __construct(
        private readonly UserRepository $users,
        private readonly array $jwtSettings,
    ) {
    }

    #[OA\Post(
        path: '/v1/auth/login',
        summary: 'Issue JWT',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'password', type: 'string', format: 'password'),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Bearer token',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
                        new OA\Property(property: 'access_token', type: 'string'),
                        new OA\Property(property: 'expires_in', type: 'integer'),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Invalid credentials'),
        ],
    )]
    /**
     * Weryfikuje e-mail i hasło, a przy sukcesie zwraca token JWT (access token) w formacie JSON.
     *
     * @param array<string, string> $args Parametry trasy (puste)
     */
    public function login(ServerRequestInterface $request, ResponseInterface $response, array $args = []): ResponseInterface
    {
        $body = (string) $request->getBody();
        /** @var array<string, mixed> $data */
        $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        $email = isset($data['email']) && is_string($data['email']) ? $data['email'] : '';
        $password = isset($data['password']) && is_string($data['password']) ? $data['password'] : '';

        $user = $this->users->findByEmail($email);
        if ($user === null || !password_verify($password, $user->passwordHash)) {
            $response = new Response(401);
            $response->getBody()->write(json_encode([
                'type' => 'about:blank',
                'title' => 'Unauthorized',
                'status' => 401,
                'detail' => 'Invalid email or password.',
            ], JSON_THROW_ON_ERROR));

            return $response->withHeader('Content-Type', 'application/problem+json; charset=utf-8');
        }

        $secret = (string) ($this->jwtSettings['secret'] ?? '');
        $issuer = (string) ($this->jwtSettings['issuer'] ?? '');
        $audience = (string) ($this->jwtSettings['audience'] ?? '');
        $algorithm = (string) ($this->jwtSettings['algorithm'] ?? 'HS256');

        $now = time();
        $ttl = 3600;
        $payload = [
            'iss' => $issuer,
            'aud' => $audience,
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $ttl,
            'sub' => (string) $user->id,
        ];

        $token = JWT::encode($payload, $secret, $algorithm);

        $response = new Response(200);
        $response->getBody()->write(json_encode([
            'token_type' => 'Bearer',
            'access_token' => $token,
            'expires_in' => $ttl,
        ], JSON_THROW_ON_ERROR));

        return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}
