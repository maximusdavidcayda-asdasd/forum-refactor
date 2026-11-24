<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

final class CommentModel
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findByDiscussionId(int $discussionId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.id, c.discussion_id, c.parent_id, c.user_id, c.body, c.created_at,
                    u.username AS author_name, u.avatar_path
             FROM comments c
             INNER JOIN users u ON u.id = c.user_id
             WHERE c.discussion_id = :discussion_id
             ORDER BY c.created_at ASC, c.id ASC'
        );
        $stmt->execute([':discussion_id' => $discussionId]);

        return $stmt->fetchAll();
    }

    public function findParentInDiscussion(int $parentId, int $discussionId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, discussion_id, parent_id
             FROM comments
             WHERE id = :id AND discussion_id = :discussion_id
             LIMIT 1'
        );
        $stmt->execute([
            ':id'            => $parentId,
            ':discussion_id' => $discussionId,
        ]);

        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(int $discussionId, ?int $parentId, int $userId, string $body): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO comments (discussion_id, parent_id, user_id, body)
             VALUES (:discussion_id, :parent_id, :user_id, :body)'
        );
        $stmt->bindValue(':discussion_id', $discussionId, PDO::PARAM_INT);
        if ($parentId === null) {
            $stmt->bindValue(':parent_id', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':parent_id', $parentId, PDO::PARAM_INT);
        }
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':body', $body, PDO::PARAM_STR);
        $stmt->execute();

        return (int) $this->pdo->lastInsertId();
    }
}   