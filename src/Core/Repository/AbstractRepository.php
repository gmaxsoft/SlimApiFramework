<?php

declare(strict_types=1);

namespace App\Core\Repository;

use PDO;

/**
 * Klasa bazowa repozytoriów modułowych: zapytania SQL pozostają w klasach repozytoriów (bez ORM).
 * Moduły rozszerzają tę klasę i otrzymują współdzielone PDO z kontenera zależności.
 */
abstract class AbstractRepository
{
    /**
     * @param PDO $pdo Współdzielone połączenie PDO z serwisu kontenera
     */
    public function __construct(
        protected readonly PDO $pdo,
    ) {
    }
}
