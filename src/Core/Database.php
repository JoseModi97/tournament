<?php

namespace App\Core;

use App\Config;
use PDO;
use PDOException;

class Database
{
    private static ?PDO $connection = null;

    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            $dsn = 'sqlite:' . Config::DB_PATH;
            try {
                self::$connection = new PDO($dsn);
                self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$connection->exec('PRAGMA foreign_keys = ON');
            } catch (PDOException $e) {
                http_response_code(500);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Database connection failed', 'details' => $e->getMessage()]);
                exit;
            }
        }

        return self::$connection;
    }
}
