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

    public function findAll(): array
    {
        $sql = "
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
                u.login as user_login,
                GROUP_CONCAT(tags.name SEPARATOR ',') as tag_names
            FROM tickets t
            JOIN statuses s ON t.status_id = s.id
            JOIN users u ON t.user_id = u.id
            LEFT JOIN ticket_tags tt ON t.id = tt.ticket_id
            LEFT JOIN tags ON tt.tag_id = tags.id
            GROUP BY t.id
            ORDER BY t.created_at DESC
        ";

        try {
            $stmt = $this->db->query($sql);
            $tickets = $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log("SQL Error in findAll: " . $e->getMessage());
            return [];
        }

        foreach ($tickets as &$ticket) {
            if (!empty($ticket['tag_names'])) {
                $ticket['tags'] = explode(',', $ticket['tag_names']);
            } else {
                $ticket['tags'] = [];
            }
            unset($ticket['tag_names']);
        }

        return $tickets;
    }

    public function find(int $id): ?array
    {
        $sql = $this->getBaseSelect() . " WHERE t.id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);

        $result = $stmt->fetch();

        if (!$result) {
            return null;
        }

        $stmtTags = $this->db->prepare("
            SELECT t.name 
            FROM tags t
            JOIN ticket_tags tt ON t.id = tt.tag_id
            WHERE tt.ticket_id = ?
        ");

        $stmtTags->execute([$id]);
        $result['tags'] = $stmtTags->fetchAll(PDO::FETCH_COLUMN);

        return $result;
    }

    public function create(int $userId, int $statusId, string $title, string $description): int
    {
        $sql = "INSERT INTO tickets (user_id, status_id, title, description) VALUES (?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $statusId, $title, $description]);

        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, string $statusCode, string $title, string $description, array $tags = []): void
    {
        $oldTicket = $this->find($id);
        $oldStatusCode = $oldTicket['status_code'] ?? '';

        $newStatusId = $this->findStatusIdByCode($statusCode);

        $stmt = $this->db->prepare("
            UPDATE tickets 
            SET title = :title, 
                description = :description, 
                status_id = :status_id 
            WHERE id = :id
        ");

        $stmt->execute([
            ':title' => $title,
            ':description' => $description,
            ':status_id' => $newStatusId,
            ':id' => $id
        ]);

        if ($oldStatusCode !== $statusCode) {
            $userId = $_SESSION['user_id'] ?? 1;

            $stmtLog = $this->db->prepare("
                INSERT INTO comments (ticket_id, user_id, text) 
                VALUES (?, ?, ?)
            ");
            $text = "System: Status changed from '{$oldStatusCode}' to '{$statusCode}'";
            $stmtLog->execute([$id, $userId, $text]);
        }

        $stmtDel = $this->db->prepare("DELETE FROM ticket_tags WHERE ticket_id = ?");
        $stmtDel->execute([$id]);

        if (!empty($tags)) {
            $this->attachTags($id, $tags);
        }
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

    public function attachTags(int $ticketId, array $tagNames): void
    {
        if (empty($tagNames)) {
            return;
        }

        $placeholders = implode(',', array_fill(0, count($tagNames), '?'));

        $sql = "SELECT id FROM tags WHERE name IN ($placeholders)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($tagNames);
        $tags = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $insertSql = "INSERT INTO ticket_tags (ticket_id, tag_id) VALUES (?, ?)";
        $stmt = $this->db->prepare($insertSql);

        foreach ($tags as $tagId) {
            try {
                $stmt->execute([$ticketId, $tagId]);
            } catch (\PDOException $e) {
                // ticket_tag connection already exists
            }
        }
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