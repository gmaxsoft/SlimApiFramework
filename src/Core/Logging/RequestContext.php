<?php

declare(strict_types=1);

namespace App\Core\Logging;

/**
 * Przechowuje wartości kontekstu bieżącego żądania dla loggera (bez przekazywania obiektu Request do każdego serwisu).
 * Ustawiane przez middleware na początku potoku i czyszczone po wysłaniu odpowiedzi.
 */
final class RequestContext
{
    private static ?string $requestId = null;

    /**
     * Zapisuje identyfikator żądania używany przez procesory Monolog.
     */
    public static function setRequestId(?string $requestId): void
    {
        self::$requestId = $requestId;
    }

    /**
     * Zwraca bieżący identyfikator żądania lub null, jeśli nie został ustawiony.
     */
    public static function getRequestId(): ?string
    {
        return self::$requestId;
    }

    /**
     * Czyści kontekst po zakończeniu obsługi żądania (zapobiega przeciekowi między żądaniami w tym samym procesie).
     */
    public static function reset(): void
    {
        self::$requestId = null;
    }
}
