<?php

declare(strict_types=1);

namespace App\Core\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Globalne metadane OpenAPI scalane przez swagger-php podczas skanowania katalogu `src`.
 * Atrybuty definiują wersję specyfikacji, Info, serwer domyślny oraz schemat zabezpieczeń Bearer JWT.
 */
#[OA\OpenApi(openapi: '3.0.0')]
#[OA\Info(title: 'Modular Monolith API', version: '1.0.0')]
#[OA\Server(url: '/', description: 'API root')]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
    description: 'JWT from POST /v1/auth/login',
)]
final class OpenApiDefinition
{
}
