<?php

declare(strict_types=1);

namespace App\Modules\User\Models;

/**
 * Model domenowy agregatu User (lokalny dla modułu; bez mapowania ORM).
 */
final readonly class User
{
    /**
     * @param int    $id           Identyfikator rekordu w bazie
     * @param string $email        Adres e-mail (unikalny)
     * @param string $name         Wyświetlana nazwa użytkownika
     * @param string $passwordHash Skrót hasła (np. bcrypt)
     * @param string $createdAt    Data utworzenia w formacie zwracanym przez MySQL
     */
    public function __construct(
        public int $id,
        public string $email,
        public string $name,
        public string $passwordHash,
        public string $createdAt,
    ) {
    }
}
