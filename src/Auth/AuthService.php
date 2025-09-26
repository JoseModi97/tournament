<?php

namespace App\Auth;

use App\Config;
use App\Core\Database;
use App\Core\Response;
use DateInterval;
use DateTimeImmutable;
use PDO;

class AuthService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function register(array $data): void
    {
        $name = trim($data['name'] ?? '');
        $email = strtolower(trim($data['email'] ?? ''));
        $password = $data['password'] ?? '';
        $role = $data['role'] ?? 'user';

        if ($name === '' || $email === '' || $password === '') {
            Response::json(['error' => 'Name, email, and password are required'], 422);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::json(['error' => 'Invalid email address'], 422);
            return;
        }

        $allowedRoles = ['user', 'admin', 'staff'];
        if (!in_array($role, $allowedRoles, true)) {
            $role = 'user';
        }

        $stmt = $this->db->prepare('SELECT id FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        if ($stmt->fetch()) {
            Response::json(['error' => 'Email already registered'], 409);
            return;
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare('INSERT INTO users (name, email, password_hash, role, wallet_balance, created_at) VALUES (:name, :email, :password_hash, :role, 0, :created_at)');
        $stmt->execute([
            'name' => $name,
            'email' => $email,
            'password_hash' => $passwordHash,
            'role' => $role,
            'created_at' => (new DateTimeImmutable())->format(DATE_ATOM),
        ]);

        Response::json(['message' => 'Registration successful']);
    }

    public function login(array $data): void
    {
        $email = strtolower(trim($data['email'] ?? ''));
        $password = $data['password'] ?? '';

        if ($email === '' || $password === '') {
            Response::json(['error' => 'Email and password are required'], 422);
            return;
        }

        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            Response::json(['error' => 'Invalid credentials'], 401);
            return;
        }

        $token = bin2hex(random_bytes(32));
        $expires = (new DateTimeImmutable())->add(new DateInterval('PT' . Config::TOKEN_TTL_MINUTES . 'M'));

        $stmt = $this->db->prepare('INSERT INTO tokens (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)');
        $stmt->execute([
            'user_id' => $user['id'],
            'token' => $token,
            'expires_at' => $expires->format(DATE_ATOM),
        ]);

        Response::json([
            'token' => $token,
            'expires_at' => $expires->format(DATE_ATOM),
            'user' => [
                'id' => (int) $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role'],
            ],
        ]);
    }

    public function requireAuth(array $roles = []): ?array
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;
        if (!$authHeader || !preg_match('/Bearer\s+(\S+)/', $authHeader, $matches)) {
            Response::json(['error' => 'Authorization header missing'], 401);
            return null;
        }

        $token = $matches[1];
        $stmt = $this->db->prepare('SELECT t.token, t.expires_at, u.* FROM tokens t JOIN users u ON u.id = t.user_id WHERE t.token = :token');
        $stmt->execute(['token' => $token]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$result) {
            Response::json(['error' => 'Invalid token'], 401);
            return null;
        }

        if (new DateTimeImmutable($result['expires_at']) < new DateTimeImmutable()) {
            Response::json(['error' => 'Token expired'], 401);
            return null;
        }

        if ($roles && !in_array($result['role'], $roles, true)) {
            Response::json(['error' => 'Forbidden'], 403);
            return null;
        }

        return $result;
    }
}
