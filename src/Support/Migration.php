<?php

namespace App\Support;

use App\Core\Database;
use PDO;

class Migration
{
    public static function run(): void
    {
        $db = Database::getConnection();
        self::createUsersTable($db);
        self::createTokensTable($db);
        self::createTournamentsTable($db);
        self::createMatchesTable($db);
        self::createRegistrationsTable($db);
        self::createWalletTransactionsTable($db);
        self::createWithdrawalsTable($db);
    }

    private static function createUsersTable(PDO $db): void
    {
        $db->exec('CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            role TEXT NOT NULL,
            wallet_balance REAL NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL
        )');
    }

    private static function createTokensTable(PDO $db): void
    {
        $db->exec('CREATE TABLE IF NOT EXISTS tokens (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            token TEXT NOT NULL UNIQUE,
            expires_at TEXT NOT NULL,
            FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
        )');
    }

    private static function createTournamentsTable(PDO $db): void
    {
        $db->exec('CREATE TABLE IF NOT EXISTS tournaments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            description TEXT,
            entry_fee REAL NOT NULL DEFAULT 0,
            prize_pool REAL NOT NULL DEFAULT 0,
            start_time TEXT NOT NULL,
            status TEXT NOT NULL,
            created_by INTEGER,
            created_at TEXT NOT NULL,
            FOREIGN KEY(created_by) REFERENCES users(id)
        )');
    }

    private static function createMatchesTable(PDO $db): void
    {
        $db->exec('CREATE TABLE IF NOT EXISTS matches (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            tournament_id INTEGER NOT NULL,
            name TEXT NOT NULL,
            status TEXT NOT NULL,
            result_text TEXT,
            assigned_staff_id INTEGER,
            created_at TEXT NOT NULL,
            FOREIGN KEY(tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE,
            FOREIGN KEY(assigned_staff_id) REFERENCES users(id)
        )');
    }

    private static function createRegistrationsTable(PDO $db): void
    {
        $db->exec('CREATE TABLE IF NOT EXISTS registrations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            tournament_id INTEGER NOT NULL,
            status TEXT NOT NULL,
            created_at TEXT NOT NULL,
            UNIQUE(user_id, tournament_id),
            FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY(tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE
        )');
    }

    private static function createWalletTransactionsTable(PDO $db): void
    {
        $db->exec('CREATE TABLE IF NOT EXISTS wallet_transactions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            type TEXT NOT NULL,
            amount REAL NOT NULL,
            status TEXT NOT NULL,
            created_at TEXT NOT NULL,
            metadata TEXT,
            FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
        )');
    }

    private static function createWithdrawalsTable(PDO $db): void
    {
        $db->exec('CREATE TABLE IF NOT EXISTS withdrawal_requests (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            amount REAL NOT NULL,
            status TEXT NOT NULL,
            created_at TEXT NOT NULL,
            reviewed_by INTEGER,
            reviewed_at TEXT,
            FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY(reviewed_by) REFERENCES users(id)
        )');
    }
}
