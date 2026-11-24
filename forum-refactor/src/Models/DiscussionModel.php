<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

final class DiscussionModel
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findAllWithAuthor(): array
    {
        $stmt = $this->pdo->query(
            'SELECT d.id, d.title, d.body, d.created_at, u.username AS author_name
             FROM discussions d
             INNER JOIN users u ON u.id = d.user_id
             ORDER BY d.id ASC'
        );

        return $stmt->fetchAll();
    }

    public function findByIdWithAuthor(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT d.id, d.user_id, d.title, d.body, d.created_at, u.username AS author_name
             FROM discussions d
             INNER JOIN users u ON u.id = d.user_id
             WHERE d.id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $id]);

        $row = $stmt->fetch();
        return $row ?: null;
    }
}