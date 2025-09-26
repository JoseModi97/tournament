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

$tz = new \DateTimeZone('UTC');

$users = [
    [
        'name' => 'Admin User',
        'email' => 'admin@example.com',
        'password' => 'admin123',
        'role' => 'admin',
        'created_at' => new \DateTimeImmutable('2024-05-01 08:00:00', $tz),
    ],
    [
        'name' => 'Staff User',
        'email' => 'staff@example.com',
        'password' => 'staff123',
        'role' => 'staff',
        'created_at' => new \DateTimeImmutable('2024-05-01 08:30:00', $tz),
    ],
    [
        'name' => 'Player One',
        'email' => 'player@example.com',
        'password' => 'player123',
        'role' => 'user',
        'created_at' => new \DateTimeImmutable('2024-05-01 09:00:00', $tz),
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
        'balance' => 0,
        'created_at' => $user['created_at']->format(DATE_ATOM),
    ]);

    echo "Created {$user['role']} {$user['email']} (password: {$user['password']})\n";
}

$userLookup = $db->prepare('SELECT id FROM users WHERE email = :email');
$userIds = [];
foreach ($users as $user) {
    $userLookup->execute(['email' => $user['email']]);
    $userIds[$user['email']] = (int) $userLookup->fetchColumn();
}

$adminId = $userIds['admin@example.com'] ?? null;
$staffId = $userIds['staff@example.com'] ?? null;
$playerId = $userIds['player@example.com'] ?? null;

if (!$adminId || !$staffId || !$playerId) {
    fwrite(STDERR, "Required seed users are missing; aborting related seeds.\n");
    exit(1);
}

$tournaments = [
    [
        'name' => 'Free Fire Solo Showdown',
        'description' => 'Solo bracket with three qualifying rounds and a grand final.',
        'entry_fee' => 20.00,
        'prize_pool' => 500.00,
        'start_time' => '2024-05-05T15:00:00+00:00',
        'status' => 'upcoming',
        'created_by' => $adminId,
        'created_at' => '2024-05-01T10:00:00+00:00',
        'matches' => [
            [
                'name' => 'Qualifier Match 1',
                'status' => 'scheduled',
                'result_text' => null,
                'assigned_staff_id' => $staffId,
                'created_at' => '2024-05-02T09:00:00+00:00',
            ],
            [
                'name' => 'Qualifier Match 2',
                'status' => 'scheduled',
                'result_text' => null,
                'assigned_staff_id' => null,
                'created_at' => '2024-05-02T10:00:00+00:00',
            ],
        ],
        'registrations' => [
            [
                'user_id' => $playerId,
                'status' => 'confirmed',
                'created_at' => '2024-05-02T12:00:00+00:00',
            ],
        ],
    ],
    [
        'name' => 'Free Fire Champions Cup',
        'description' => 'Invite-only finals for the season\'s top squads.',
        'entry_fee' => 0.00,
        'prize_pool' => 1000.00,
        'start_time' => '2024-04-25T18:00:00+00:00',
        'status' => 'completed',
        'created_by' => $adminId,
        'created_at' => '2024-04-20T10:00:00+00:00',
        'matches' => [
            [
                'name' => 'Grand Finals',
                'status' => 'completed',
                'result_text' => 'Squad Phoenix claimed the Bo5 series 3-1.',
                'assigned_staff_id' => $staffId,
                'created_at' => '2024-04-25T18:15:00+00:00',
            ],
        ],
        'registrations' => [
            [
                'user_id' => $playerId,
                'status' => 'confirmed',
                'created_at' => '2024-04-21T09:00:00+00:00',
            ],
        ],
    ],
];

$tournamentIds = [];
$findTournament = $db->prepare('SELECT id FROM tournaments WHERE name = :name');
$insertTournament = $db->prepare('INSERT INTO tournaments (name, description, entry_fee, prize_pool, start_time, status, created_by, created_at) VALUES (:name, :description, :entry_fee, :prize_pool, :start_time, :status, :created_by, :created_at)');

foreach ($tournaments as $tournament) {
    $findTournament->execute(['name' => $tournament['name']]);
    $existingId = $findTournament->fetchColumn();

    if ($existingId) {
        echo "Tournament {$tournament['name']} already exists\n";
        $tournamentId = (int) $existingId;
    } else {
        $insertTournament->execute([
            'name' => $tournament['name'],
            'description' => $tournament['description'],
            'entry_fee' => $tournament['entry_fee'],
            'prize_pool' => $tournament['prize_pool'],
            'start_time' => $tournament['start_time'],
            'status' => $tournament['status'],
            'created_by' => $tournament['created_by'],
            'created_at' => $tournament['created_at'],
        ]);
        $tournamentId = (int) $db->lastInsertId();
        echo "Created tournament {$tournament['name']}\n";
    }

    $tournamentIds[$tournament['name']] = $tournamentId;

    $matchFinder = $db->prepare('SELECT id FROM matches WHERE tournament_id = :tournament_id AND name = :name');
    $matchInsert = $db->prepare('INSERT INTO matches (tournament_id, name, status, result_text, assigned_staff_id, created_at) VALUES (:tournament_id, :name, :status, :result_text, :assigned_staff_id, :created_at)');
    foreach ($tournament['matches'] as $match) {
        $matchFinder->execute([
            'tournament_id' => $tournamentId,
            'name' => $match['name'],
        ]);
        if ($matchFinder->fetch()) {
            echo "Match {$match['name']} already exists for tournament {$tournament['name']}\n";
            continue;
        }

        $matchInsert->execute([
            'tournament_id' => $tournamentId,
            'name' => $match['name'],
            'status' => $match['status'],
            'result_text' => $match['result_text'],
            'assigned_staff_id' => $match['assigned_staff_id'],
            'created_at' => $match['created_at'],
        ]);
        echo "Created match {$match['name']} for tournament {$tournament['name']}\n";
    }

    $registrationFinder = $db->prepare('SELECT id FROM registrations WHERE user_id = :user_id AND tournament_id = :tournament_id');
    $registrationInsert = $db->prepare('INSERT INTO registrations (user_id, tournament_id, status, created_at) VALUES (:user_id, :tournament_id, :status, :created_at)');
    foreach ($tournament['registrations'] as $registration) {
        $registrationFinder->execute([
            'user_id' => $registration['user_id'],
            'tournament_id' => $tournamentId,
        ]);
        if ($registrationFinder->fetch()) {
            echo "Registration already exists for user {$registration['user_id']} in tournament {$tournament['name']}\n";
            continue;
        }

        $registrationInsert->execute([
            'user_id' => $registration['user_id'],
            'tournament_id' => $tournamentId,
            'status' => $registration['status'],
            'created_at' => $registration['created_at'],
        ]);
        echo "Registered user {$registration['user_id']} for tournament {$tournament['name']}\n";
    }
}

$walletTransactions = [
    [
        'user_id' => $playerId,
        'type' => 'deposit',
        'amount' => 100.00,
        'status' => 'completed',
        'metadata' => ['source' => 'welcome_bonus'],
        'created_at' => '2024-04-28T12:00:00+00:00',
    ],
    [
        'user_id' => $playerId,
        'type' => 'entry_fee',
        'amount' => 20.00,
        'status' => 'completed',
        'metadata' => ['tournament_id' => $tournamentIds['Free Fire Solo Showdown'] ?? null],
        'created_at' => '2024-05-02T12:30:00+00:00',
    ],
    [
        'user_id' => $playerId,
        'type' => 'withdrawal',
        'amount' => 30.00,
        'status' => 'completed',
        'metadata' => ['reference' => 'TXN-FF-0001'],
        'created_at' => '2024-05-03T10:00:00+00:00',
    ],
    [
        'user_id' => $playerId,
        'type' => 'withdrawal',
        'amount' => 25.00,
        'status' => 'pending',
        'metadata' => new \stdClass(),
        'created_at' => '2024-05-04T14:00:00+00:00',
    ],
];

$transactionFinder = $db->prepare('SELECT id FROM wallet_transactions WHERE user_id = :user_id AND type = :type AND amount = :amount AND status = :status AND created_at = :created_at');
$transactionInsert = $db->prepare('INSERT INTO wallet_transactions (user_id, type, amount, status, created_at, metadata) VALUES (:user_id, :type, :amount, :status, :created_at, :metadata)');

foreach ($walletTransactions as $transaction) {
    $transactionFinder->execute([
        'user_id' => $transaction['user_id'],
        'type' => $transaction['type'],
        'amount' => $transaction['amount'],
        'status' => $transaction['status'],
        'created_at' => $transaction['created_at'],
    ]);

    if ($transactionFinder->fetch()) {
        echo "Wallet transaction {$transaction['type']} ({$transaction['amount']}) already exists\n";
        continue;
    }

    $metadata = $transaction['metadata'];
    if (is_array($metadata) || $metadata instanceof stdClass) {
        $metadata = json_encode($metadata);
    }

    $transactionInsert->execute([
        'user_id' => $transaction['user_id'],
        'type' => $transaction['type'],
        'amount' => $transaction['amount'],
        'status' => $transaction['status'],
        'created_at' => $transaction['created_at'],
        'metadata' => $metadata,
    ]);
    echo "Created wallet transaction {$transaction['type']} ({$transaction['amount']})\n";
}

$withdrawals = [
    [
        'user_id' => $playerId,
        'amount' => 30.00,
        'status' => 'approved',
        'created_at' => '2024-05-03T10:05:00+00:00',
        'reviewed_by' => $adminId,
        'reviewed_at' => '2024-05-03T11:00:00+00:00',
    ],
    [
        'user_id' => $playerId,
        'amount' => 25.00,
        'status' => 'pending',
        'created_at' => '2024-05-04T14:05:00+00:00',
        'reviewed_by' => null,
        'reviewed_at' => null,
    ],
];

$withdrawalFinder = $db->prepare('SELECT id FROM withdrawal_requests WHERE user_id = :user_id AND amount = :amount AND status = :status AND created_at = :created_at');
$withdrawalInsert = $db->prepare('INSERT INTO withdrawal_requests (user_id, amount, status, created_at, reviewed_by, reviewed_at) VALUES (:user_id, :amount, :status, :created_at, :reviewed_by, :reviewed_at)');

foreach ($withdrawals as $request) {
    $withdrawalFinder->execute([
        'user_id' => $request['user_id'],
        'amount' => $request['amount'],
        'status' => $request['status'],
        'created_at' => $request['created_at'],
    ]);

    if ($withdrawalFinder->fetch()) {
        echo "Withdrawal request {$request['amount']} ({$request['status']}) already exists\n";
        continue;
    }

    $withdrawalInsert->execute([
        'user_id' => $request['user_id'],
        'amount' => $request['amount'],
        'status' => $request['status'],
        'created_at' => $request['created_at'],
        'reviewed_by' => $request['reviewed_by'],
        'reviewed_at' => $request['reviewed_at'],
    ]);
    echo "Created withdrawal request {$request['amount']} ({$request['status']})\n";
}

$updateWallet = $db->prepare('UPDATE users SET wallet_balance = :balance WHERE id = :id');
$updateWallet->execute([
    'balance' => 25.00,
    'id' => $playerId,
]);

echo "Updated wallet balance for player@example.com to 25.00\n";
