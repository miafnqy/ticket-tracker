<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Database;
use App\Enums\UserRole;
use JsonException;
use PDO;

class AuthController extends BaseController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * @throws JsonException
     */
    public function register(): void
    {
        $input = $this->getJsonInput();

        $this->validate($input, [
            'login'    => 'required|string|min:3|max:50',
            'password' => 'required|string|min:6|max:255'
        ]);

        $login = trim($input['login']);
        $password = $input['password'];

        $stmt = $this->db->prepare("SELECT id FROM users WHERE login = ?");
        $stmt->execute([$login]);

        if ($stmt->fetch()) {
            $this->error('User with this login already exists', 409);
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $role = UserRole::CLIENT->value;

        $stmt = $this->db->prepare("INSERT INTO users (login, password, role) VALUES (?, ?, ?)");
        $stmt->execute([$login, $passwordHash, $role]);

        $this->json(['message' => 'User registered successfully'], 201);
    }

    /**
     * @throws JsonException
     */
    public function login(): void
    {
        $input = $this->getJsonInput();

        $this->validate($input, [
            'login'    => 'required|string|min:3|max:50',
            'password' => 'required|string|min:6|max:255'
        ]);

        $stmt = $this->db->prepare("SELECT * FROM users WHERE login = ?");
        $stmt->execute([$input['login']]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($input['password'], $user['password'])) {
            $this->error('Invalid login or password', 401);
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];

        $this->json([
            'message' => 'Login successful',
            'user' => [
                'id' => $user['id'],
                'login' => $user['login'],
                'role' => $user['role']
            ]
        ]);
    }

    /**
     * @throws JsonException
     */
    public function logout(): void
    {
        session_destroy();
        $this->json(['message' => 'Logged out']);
    }
}