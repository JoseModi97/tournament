#!/usr/bin/env php
<?php

require_once __DIR__ . '/../src/autoload.php';

use App\Core\Database;

try {
    $db = Database::getConnection();
} catch (RuntimeException $e) {
    fwrite(STDERR, "Seeding aborted: {$e->getMessage()}\n");
    exit(1);
}

$users = [
    [
        'name' => 'Admin User',
        'email' => 'admin@example.com',
        'password' => 'admin123',
        'role' => 'admin',
    ],
    [
        'name' => 'Staff User',
        'email' => 'staff@example.com',
        'password' => 'staff123',
        'role' => 'staff',
    ],
    [
        'name' => 'Player One',
        'email' => 'player@example.com',
        'password' => 'player123',
        'role' => 'user',
    ],
];

foreach ($users as $user) {
    $stmt = $db->prepare('SELECT id FROM users WHERE email = :email');
    $stmt->execute(['email' => $user['email']]);
    if ($stmt->fetch()) {
        echo "User {$user['email']} already exists\n";
        continue;
    }

    $insert = $db->prepare('INSERT INTO users (name, email, password_hash, role, wallet_balance, created_at) VALUES (:name, :email, :password_hash, :role, :balance, :created_at)');
    $insert->execute([
        'name' => $user['name'],
        'email' => $user['email'],
        'password_hash' => password_hash($user['password'], PASSWORD_BCRYPT),
        'role' => $user['role'],
        'balance' => $user['role'] === 'user' ? 100 : 0,
        'created_at' => (new DateTimeImmutable())->format(DATE_ATOM),
    ]);

    echo "Created {$user['role']} {$user['email']} (password: {$user['password']})\n";
}
