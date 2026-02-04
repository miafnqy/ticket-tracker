<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use App\Core\QueryBuilder;
use PDO;

class TicketRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findAll(?int $userId = null, array $filters = []): array
    {
        $qb = $this->baseQuery();

        if ($userId) {
            $ab->where('t.user_id', '=', $userId);
        }

        if (!empty($filters['status'])) {
            $qb->where('s.code', '=', $filters['status']);
        }

        $sortMap = [
            'id' => 't.id', 'title' => 't.title',
            'created_at' => 't.created_at', 'status_code' => 's.code'
        ];

        $field = $sortMap[$filters['sort_by'] ?? ''] ?? 't.created_at';
        $qb->orderBy($field, $filters['order_by'] ?? 'DESC');

        $tickets = $qb->get();

        return $this->eagerLoadTags($tickets);
    }

    public function find(int $id): ?array
    {
        $qb = $this->baseQuery();
        $qb->where('t.id', '=', $id);

        $ticket = $qb->first();

        if (!$ticket) {
            return null;
        }

        $ticketsWithTags = $this->eagerLoadTags([$ticket]);

        return $ticketsWithTags[0];
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

    private function eagerLoadTags(array $tickets): array
    {
        if (empty($tickets)) {
            return [];
        }

        $ticketIds = array_column($tickets, 'id');

        $placeholders = str_repeat('?,', count($ticketIds) - 1) . '?';

        $sql = "
            SELECT t.id, t.name, tt.ticket_id
            FROM tags t
            JOIN ticket_tags tt ON t.id = tt.tag_id
            WHERE tt.ticket_id IN ($placeholders)
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($ticketIds);
        $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $tagsMap = [];
        foreach ($tags as $tag) {
            $tagsMap[$tag['ticket_id']][] = $tag['name'];
        }

        foreach ($tickets as &$ticket) {
            $ticket['tags'] = $tagsMap[$ticket['id']] ?? [];
        }

        return $tickets;
    }

    private function baseQuery(): QueryBuilder
    {
        $qb = new QueryBuilder($this->db);

        return $qb
            ->select([
                't.id', 't.title', 't.description', 't.created_at', 't.updated_at',
                't.user_id', 't.status_id',
                's.name as status_name', 's.code as status_code',
                'u.login as user_login'
            ])
            ->from('tickets', 't')
            ->join('statuses s', 't.status_id = s.id')
            ->join('users u', 't.user_id = u.id');
    }
}