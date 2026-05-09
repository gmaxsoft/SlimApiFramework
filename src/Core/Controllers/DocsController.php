<?php

declare(strict_types=1);

namespace App\Core\Controllers;

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Dostarcza interfejs Swagger UI pod adresem `/docs` oraz wygenerowany dokument OpenAPI pod `/openapi.json`.
 */
final class DocsController
{
    private const OPENAPI_PUBLIC = __DIR__ . '/../../../public/openapi.json';

    /**
     * Zwraca stronę HTML ładującą Swagger UI (z CDN) wskazującą na plik specyfikacji pod `/openapi.json`.
     *
     * @param array<string, string> $args Parametry trasy (puste dla tej trasy)
     */
    public function swaggerUi(ServerRequestInterface $request, ResponseInterface $response, array $args = []): ResponseInterface
    {
        $specUrl = '/openapi.json';
        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>API Documentation</title>
  <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css">
</head>
<body>
<div id="swagger-ui"></div>
<script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
<script>
  window.ui = SwaggerUIBundle({
    url: "{$specUrl}",
    dom_id: '#swagger-ui',
    presets: [SwaggerUIBundle.presets.apis],
    layout: "BaseLayout"
  });
</script>
</body>
</html>
HTML;

        $response = new Response(200);
        $response->getBody()->write($html);

        return $response->withHeader('Content-Type', 'text/html; charset=utf-8');
    }

    /**
     * Odczytuje wygenerowany plik `public/openapi.json` i zwraca go z typem `application/json`.
     *
     * @param array<string, string> $args Parametry trasy (puste dla tej trasy)
     */
    public function openApiJson(ServerRequestInterface $request, ResponseInterface $response, array $args = []): ResponseInterface
    {
        if (!is_readable(self::OPENAPI_PUBLIC)) {
            $response = new Response(503);
            $response->getBody()->write(json_encode([
                'title' => 'OpenAPI not generated',
                'detail' => 'Run `composer openapi:generate` to create public/openapi.json',
                'status' => 503,
            ], JSON_THROW_ON_ERROR));

            return $response
                ->withHeader('Content-Type', 'application/problem+json; charset=utf-8');
        }

        $json = file_get_contents(self::OPENAPI_PUBLIC);
        if ($json === false) {
            return (new Response(500))->withHeader('Content-Type', 'application/problem+json');
        }

        $response = new Response(200);
        $response->getBody()->write($json);

        return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}
