<?php

declare(strict_types=1);

namespace App\Modules\User\Services;

use App\Modules\User\Models\User;
use App\Modules\User\Repositories\UserRepository;

/**
 * Serwis aplikacyjny modułu User: koordynuje repozytorium i egzekwuje reguły modułu.
 */
final class UserService
{
    /**
     * @param UserRepository $users Repozytorium dostępu do danych użytkowników
     */
    public function __construct(
        private readonly UserRepository $users,
    ) {
    }

    /**
     * Zwraca paginowaną listę użytkowników.
     *
     * @return list<User>
     */
    public function listUsers(int $limit = 50, int $offset = 0): array
    {
        return $this->users->findAll($limit, $offset);
    }

    /**
     * Pobiera pojedynczego użytkownika po id lub null.
     */
    public function getUser(int $id): ?User
    {
        return $this->users->findById($id);
    }

    /**
     * Rejestruje nowego użytkownika po walidacji unikalności e-maila i zahashowaniu hasła.
     *
     * @throws \InvalidArgumentException Gdy e-mail jest już zajęty
     */
    public function register(string $email, string $name, string $plainPassword): User
    {
        if ($this->users->findByEmail($email) !== null) {
            throw new \InvalidArgumentException('Email already registered.');
        }

        $hash = password_hash($plainPassword, PASSWORD_DEFAULT);

        return $this->users->create($email, $name, $hash);
    }
}
