<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\BaseController;
use App\Core\Database;
use App\Enums\UserRole;
use JsonException;
use PDO;

class TagController extends BaseController
{
    private PDO $db;

    /**
     * @throws JsonException
     */
    public function __construct()
    {
        if ($_SESSION['role'] !== UserRole::ADMIN->value) {
            $this->error('Access denied', 403);
        }
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * @throws JsonException
     */
    public function index(): void
    {
        $stmt = $this->db->query("SELECT * FROM tags ORDER BY name ASC");
        $this->json(['tags' => $stmt->fetchAll()]);
    }

    /**
     * @throws JsonException
     */
    public function store(): void
    {
        $input = $this->getJsonInput();
        $this->validate($input, ['name' => 'required|string|min:1|max:50']);

        $stmt = $this->db->prepare("SELECT id FROM tags WHERE name = ?");
        $stmt->execute([$input['name']]);
        if ($stmt->fetch()) {
            $this->error('Tag already exists', 422);
        }

        $stmt = $this->db->prepare("INSERT INTO tags (name) VALUES (?)");
        $stmt->execute([$input['name']]);

        $this->json(['message' => 'Tag created', 'id' => $this->db->lastInsertId()], 201);
    }

    /**
     * @throws JsonException
     */
    public function destroy(string $id): void
    {
        $stmt = $this->db->prepare("DELETE FROM tags WHERE id = ?");
        $stmt->execute([$id]);
        $this->json(['message' => 'Tag deleted']);
    }
}