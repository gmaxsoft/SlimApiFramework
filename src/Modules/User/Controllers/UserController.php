<?php

declare(strict_types=1);

namespace App\Modules\User\Controllers;

use App\Modules\User\Services\UserService;
use Nyholm\Psr7\Response;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Warstwa HTTP modułu User; dokumentacja endpointów przez atrybuty swagger-php (OpenAPI).
 */
final class UserController
{
    /**
     * @param UserService $users Serwis domenowy operacji na użytkownikach
     */
    public function __construct(
        private readonly UserService $users,
    ) {
    }

    #[OA\Get(
        path: '/v1/users',
        summary: 'List users',
        tags: ['Users'],
        parameters: [
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 50)),
            new OA\Parameter(name: 'offset', in: 'query', schema: new OA\Schema(type: 'integer', default: 0)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paged users',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/UserDto'),
                ),
            ),
        ],
    )]
    /**
     * Zwraca listę użytkowników z obsługą parametrów zapytania `limit` (1–100) i `offset`.
     *
     * @param array<string, string> $args Parametry trasy (puste dla listy)
     */
    public function index(ServerRequestInterface $request, ResponseInterface $response, array $args = []): ResponseInterface
    {
        $params = $request->getQueryParams();
        $limit = isset($params['limit']) ? max(1, min(100, (int) $params['limit'])) : 50;
        $offset = isset($params['offset']) ? max(0, (int) $params['offset']) : 0;

        $list = $this->users->listUsers($limit, $offset);
        $payload = array_map(static fn ($u) => [
            'id' => $u->id,
            'email' => $u->email,
            'name' => $u->name,
            'created_at' => $u->createdAt,
        ], $list);

        $response = new Response(200);
        $response->getBody()->write(json_encode($payload, JSON_THROW_ON_ERROR));

        return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
    }

    #[OA\Get(
        path: '/v1/users/{id}',
        summary: 'Get user by id',
        tags: ['Users'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'User',
                content: new OA\JsonContent(ref: '#/components/schemas/UserDto'),
            ),
            new OA\Response(response: 404, description: 'Not found'),
        ],
    )]
    /**
     * Zwraca jednego użytkownika po identyfikatorze z segmentu ścieżki lub odpowiedź 404 (Problem Details).
     *
     * @param array<string, string> $args Tablica z kluczem `id` z FastRoute
     */
    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $user = $this->users->getUser($id);
        if ($user === null) {
            $response = new Response(404);
            $response->getBody()->write(json_encode([
                'type' => 'about:blank',
                'title' => 'Not Found',
                'status' => 404,
                'detail' => 'User not found.',
            ], JSON_THROW_ON_ERROR));

            return $response->withHeader('Content-Type', 'application/problem+json; charset=utf-8');
        }

        $response = new Response(200);
        $response->getBody()->write(json_encode([
            'id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'created_at' => $user->createdAt,
        ], JSON_THROW_ON_ERROR));

        return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
    }

    #[OA\Post(
        path: '/v1/users',
        summary: 'Register a user',
        tags: ['Users'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'name', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'password', type: 'string', format: 'password'),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Created',
                content: new OA\JsonContent(ref: '#/components/schemas/UserDto'),
            ),
            new OA\Response(response: 409, description: 'Email taken'),
        ],
    )]
    /**
     * Tworzy nowego użytkownika z treści JSON (e-mail, nazwa, hasło); przy konflikcie e-maila zwraca 409.
     *
     * @param array<string, string> $args Parametry trasy (puste)
     */
    public function create(ServerRequestInterface $request, ResponseInterface $response, array $args = []): ResponseInterface
    {
        $body = (string) $request->getBody();
        /** @var array<string, mixed> $data */
        $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        $email = isset($data['email']) && is_string($data['email']) ? $data['email'] : '';
        $name = isset($data['name']) && is_string($data['name']) ? $data['name'] : '';
        $password = isset($data['password']) && is_string($data['password']) ? $data['password'] : '';

        try {
            $user = $this->users->register($email, $name, $password);
        } catch (\InvalidArgumentException $e) {
            $response = new Response(409);
            $response->getBody()->write(json_encode([
                'type' => 'about:blank',
                'title' => 'Conflict',
                'status' => 409,
                'detail' => $e->getMessage(),
            ], JSON_THROW_ON_ERROR));

            return $response->withHeader('Content-Type', 'application/problem+json; charset=utf-8');
        }

        $response = new Response(201);
        $response->getBody()->write(json_encode([
            'id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'created_at' => $user->createdAt,
        ], JSON_THROW_ON_ERROR));

        return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
    }

    #[OA\Get(
        path: '/v1/users/me',
        summary: 'Current user from JWT',
        security: [['bearerAuth' => []]],
        tags: ['Users'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Profile',
                content: new OA\JsonContent(ref: '#/components/schemas/UserDto'),
            ),
            new OA\Response(response: 401, description: 'Missing or invalid token'),
        ],
    )]
    /**
     * Zwraca profil użytkownika na podstawie roszczenia `sub` z tokenu JWT (atrybut żądania ustawiany przez middleware).
     *
     * @param array<string, string> $args Parametry trasy (puste)
     */
    public function me(ServerRequestInterface $request, ResponseInterface $response, array $args = []): ResponseInterface
    {
        /** @var mixed $token */
        $token = $request->getAttribute('jwt');
        $claims = is_array($token)
            ? $token
            : (array) json_decode(json_encode($token, JSON_THROW_ON_ERROR), true);
        if ($claims === []) {
            $response = new Response(401);
            $response->getBody()->write(json_encode([
                'type' => 'about:blank',
                'title' => 'Unauthorized',
                'status' => 401,
                'detail' => 'Authentication required.',
            ], JSON_THROW_ON_ERROR));

            return $response->withHeader('Content-Type', 'application/problem+json; charset=utf-8');
        }

        $sub = isset($claims['sub']) ? (int) $claims['sub'] : 0;
        $user = $this->users->getUser($sub);
        if ($user === null) {
            $response = new Response(404);
            $response->getBody()->write(json_encode([
                'type' => 'about:blank',
                'title' => 'Not Found',
                'status' => 404,
                'detail' => 'User no longer exists.',
            ], JSON_THROW_ON_ERROR));

            return $response->withHeader('Content-Type', 'application/problem+json; charset=utf-8');
        }

        $response = new Response(200);
        $response->getBody()->write(json_encode([
            'id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'created_at' => $user->createdAt,
        ], JSON_THROW_ON_ERROR));

        return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}
