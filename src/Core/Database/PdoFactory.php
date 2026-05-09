<?php

declare(strict_types=1);

namespace App\Core\Database;

use PDO;
use PDOException;

/**
 * Fabryka współdzielonego połączenia PDO z sensownymi domyślnymi ustawieniami dla MySQL.
 */
final class PdoFactory
{
    /**
     * Tworzy instancję PDO na podstawie sekcji konfiguracji `db` (host, port, baza, użytkownik, hasło, charset).
     *
     * @param array<string, mixed> $dbConfig Pełna sekcja `db` z ustawień aplikacji
     *
     * @throws PDOException Gdy połączenie z serwerem bazy się nie powiedzie
     */
    public function create(array $dbConfig): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $dbConfig['host'],
            $dbConfig['port'],
            $dbConfig['name'],
            $dbConfig['charset'],
        );

        try {
            $pdo = new PDO($dsn, (string) $dbConfig['user'], (string) $dbConfig['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            throw new PDOException('Database connection failed: ' . $e->getMessage(), (int) $e->getCode(), $e);
        }

        return $pdo;
    }
}
