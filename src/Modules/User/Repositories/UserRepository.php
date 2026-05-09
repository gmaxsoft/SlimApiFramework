<?php

declare(strict_types=1);

namespace App\Modules\User\Repositories;

use App\Core\Repository\AbstractRepository;
use App\Modules\User\Models\User;

/**
 * Persystencja użytkowników — zapytania SQL pozostają w repozytorium dla przejrzystości i przewidywalnych planów zapytań.
 */
final class UserRepository extends AbstractRepository
{
    /**
     * Zwraca listę użytkowników z paginacją (LIMIT/OFFSET).
     *
     * @return list<User>
     */
    public function findAll(int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, email, name, password_hash, created_at
             FROM users
             ORDER BY id ASC
             LIMIT :limit OFFSET :offset',
        );
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        return array_map(fn (array $r): User => $this->map($r), $rows);
    }

    /**
     * Wyszukuje użytkownika po identyfikatorze lub zwraca null, jeśli rekord nie istnieje.
     */
    public function findById(int $id): ?User
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, email, name, password_hash, created_at FROM users WHERE id = :id LIMIT 1',
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row !== false ? $this->map($row) : null;
    }

    /**
     * Wyszukuje użytkownika po adresie e-mail (porównanie po znormalizowanej małymi literami wartości).
     */
    public function findByEmail(string $email): ?User
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, email, name, password_hash, created_at FROM users WHERE email = :email LIMIT 1',
        );
        $stmt->execute(['email' => mb_strtolower($email)]);
        $row = $stmt->fetch();

        return $row !== false ? $this->map($row) : null;
    }

    /**
     * Wstawia nowego użytkownika i zwraca wczytany model po uzyskaniu identyfikatora z `lastInsertId`.
     *
     * @throws \RuntimeException Gdy odczyt po wstawieniu się nie powiedzie
     */
    public function create(string $email, string $name, string $passwordHash): User
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (email, name, password_hash) VALUES (:email, :name, :ph)',
        );
        $stmt->execute([
            'email' => mb_strtolower($email),
            'name' => $name,
            'ph' => $passwordHash,
        ]);
        $id = (int) $this->pdo->lastInsertId();

        return $this->findById($id)
            ?? throw new \RuntimeException('Failed to load user after insert.');
    }

    /**
     * Mapuje pojedynczy wiersz wyniku zapytania SQL na obiekt modelu `User`.
     *
     * @param array<string, mixed> $row Asocjacyjna tablica kolumn z PDO
     */
    private function map(array $row): User
    {
        return new User(
            id: (int) $row['id'],
            email: (string) $row['email'],
            name: (string) $row['name'],
            passwordHash: (string) $row['password_hash'],
            createdAt: (string) $row['created_at'],
        );
    }
}
