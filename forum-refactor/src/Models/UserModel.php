<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

final class UserModel
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findByUsernameOrEmail(string $identifier): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, username, email, password_hash, avatar_path, created_at
             FROM users
             WHERE username = :username OR email = :email
             LIMIT 1'
        );
        $stmt->execute([
            ':username' => $identifier,
            ':email'    => $identifier,
        ]);

        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function existsByUsernameOrEmail(string $username, string $email): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1
             FROM users
             WHERE username = :username OR email = :email
             LIMIT 1'
        );
        $stmt->execute([
            ':username' => $username,
            ':email'    => $email,
        ]);

        return (bool) $stmt->fetchColumn();
    }

    public function create(string $username, string $email, string $passwordHash): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (username, email, password_hash)
             VALUES (:username, :email, :password_hash)'
        );
        $stmt->execute([
            ':username'      => $username,
            ':email'         => $email,
            ':password_hash' => $passwordHash,
        ]);

        return (int) $this->pdo->lastInsertId();
    }
}