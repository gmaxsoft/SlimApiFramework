<?php

declare(strict_types=1);

namespace App\Modules\User\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Schemat OpenAPI `UserDto` używany w dokumentacji odpowiedzi API (skanowanie atrybutów swagger-php).
 */
#[OA\Schema(
    schema: 'UserDto',
    required: ['id', 'email', 'name', 'created_at'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64'),
        new OA\Property(property: 'email', type: 'string', format: 'email'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ],
)]
final class UserDtoSchema
{
}
