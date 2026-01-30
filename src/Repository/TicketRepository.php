<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use PDO;

class TicketRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findAll(?int $userId = null): array
    {
        $sql = $this->getBaseSelect();

        if ($userId) {
            $sql .= " WHERE t.user_id = :user_id";
        }

        $sql .= " ORDER BY t.created_at DESC";

        $stmt = $this->db->prepare($sql);

        if ($userId) {
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        }

        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $sql = $this->getBaseSelect() . " WHERE t.id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);

        $result = $stmt->fetch();

        return $result ?: null;
    }

    public function create(int $userId, int $statusId, string $title, string $description): int
    {
        $sql = "INSERT INTO tickets (user_id, status_id, title, description) VALUES (?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $statusId, $title, $description]);

        return (int)$this->db->lastInsertId();
    }

    public function updateStatus(int $ticketId, int $newStatusId): void
    {
        $stmt = $this->db->prepare("UPDATE tickets SET status_id = ? WHERE id = ?");
        $stmt->execute([$newStatusId, $ticketId]);
    }

    // move to status repository later
    public function findStatusIdByCode(string $code): ?int
    {
        $stmt = $this->db->prepare("SELECT id FROM statuses WHERE code = ?");
        $stmt->execute([$code]);
        $id = $stmt->fetchColumn();

        return $id ? (int)$id : null;
    }

    private function getBaseSelect(): string
    {
        return "
            SELECT 
                t.id, 
                t.user_id, 
                t.status_id, 
                t.title, 
                t.description, 
                t.created_at, 
                t.updated_at,
                s.name as status_name, 
                s.code as status_code,
                u.login as user_login
            FROM tickets t
            JOIN statuses s ON t.status_id = s.id
            JOIN users u ON t.user_id = u.id
        ";
    }
}