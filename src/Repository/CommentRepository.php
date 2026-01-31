<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use PDO;

class CommentRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findAllByTicketId(int $ticketId): array
    {
        $sql = $this->getBaseSelect() . " WHERE c.ticket_id = :ticket_id ORDER BY c.created_at ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':ticket_id' => $ticketId]);

        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $sql = $this->getBaseSelect() . " WHERE c.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function create(int $ticketId, int $userId, string $text): int
    {
        $sql = "INSERT INTO comments (ticket_id, user_id, text) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$ticketId, $userId, $text]);
        return (int)$this->db->lastInsertId();
    }

    private function getBaseSelect(): string
    {
        return "
            SELECT 
                c.id,
                c.ticket_id,
                c.user_id,
                c.text,
                c.created_at,
                u.login as user_login,
                u.role as user_role
            FROM comments c
            JOIN users u ON c.user_id = u.id
        ";
    }
}