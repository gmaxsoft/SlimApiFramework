<?php

declare(strict_types=1);

namespace App\Core\Http;

/**
 * Reprezentacja szczegółów problemu HTTP zgodnie z RFC 7807 (typ MIME application/problem+json).
 */
final class ProblemDetails
{
    /**
     * @param array<string, mixed> $extensions Dodatkowe pola odpowiedzi (np. błędy walidacji)
     */
    public function __construct(
        private readonly string $title,
        private readonly int $status,
        private readonly string $detail = '',
        private readonly string $type = 'about:blank',
        private readonly string $instance = '',
        private readonly array $extensions = [],
    ) {
    }

    /**
     * Zwraca treść problemu jako tablicę gotową do serializacji JSON.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $payload = [
            'type' => $this->type,
            'title' => $this->title,
            'status' => $this->status,
            'detail' => $this->detail,
        ];
        if ($this->instance !== '') {
            $payload['instance'] = $this->instance;
        }

        return array_merge($payload, $this->extensions);
    }
}
