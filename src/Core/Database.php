<?php

namespace App\Core;

use App\Config;
use PDO;
use PDOException;
use RuntimeException;

class Database
{
    private static ?PDO $connection = null;

    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            $dsn = 'sqlite:' . Config::DB_PATH;
            try {
                if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
                    $message = 'The PDO SQLite driver is not available. Enable the pdo_sqlite extension in your php.ini or install the php-sqlite3 package.';
                    self::handleConnectionFailure('SQLite driver missing', $message);
                }

                self::$connection = new PDO($dsn);
                self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$connection->exec('PRAGMA foreign_keys = ON');
            } catch (PDOException $e) {
                self::handleConnectionFailure('Database connection failed', $e->getMessage());
            }
        }

        return self::$connection;
    }

    private static function handleConnectionFailure(string $message, string $details): void
    {
        if (PHP_SAPI === 'cli') {
            throw new RuntimeException($message . ': ' . $details);
        }

        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => $message, 'details' => $details]);
        exit;
    }
}
