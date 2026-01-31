<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\BaseController;
use App\Core\Database;
use App\Enums\UserRole;
use JsonException;
use PDO;

class StatusController extends BaseController
{
    private PDO $db;

    /**
     * @throws JsonException
     */
    public function __construct()
    {
        if ($_SESSION['role'] !== UserRole::ADMIN->value) {
            $this->error('Access denied. Admins only.', 403);
        }

        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * @throws JsonException
     */
    public function index(): void
    {
        $stmt = $this->db->query("SELECT * FROM statuses ORDER BY id ASC");
        $this->json(['statuses' => $stmt->fetchAll()]);
    }

    /**
     * @throws JsonException
     */
    public function store(): void
    {
        $input = $this->getJsonInput();
        $this->validate($input, [
            'name' => 'required|string|min:1|max:50',
            'code' => 'required|string|min:1|max:50'
        ]);

        $stmt = $this->db->prepare("INSERT INTO statuses (name, code) VALUES (?, ?)");
        $stmt->execute([$input['name'], $input['code']]);

        $this->json(['message' => 'Status created'], 201);
    }

    /**
     * @throws JsonException
     */
    public function destroy(string $id): void
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM tickets WHERE status_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            $this->error('Cannot delete status: it is used in tickets.', 409);
        }

        $stmt = $this->db->prepare("DELETE FROM statuses WHERE id = ?");
        $stmt->execute([$id]);

        $this->json(['message' => 'Status deleted']);
    }
}