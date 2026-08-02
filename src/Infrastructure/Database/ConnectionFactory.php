<?php

declare(strict_types=1);

namespace BackupCatalog\Infrastructure\Database;

use PDO;

final class ConnectionFactory
{
    public static function create(string $dsn, string $user, string $password): PDO
    {
        $pdo = new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ]);
        $pdo->exec("SET TIME ZONE 'UTC'");

        return $pdo;
    }
}
