<?php

declare(strict_types=1);

namespace BackupCatalog\Tests\Integration;

use BackupCatalog\Infrastructure\Database\ConnectionFactory;
use BackupCatalog\Infrastructure\Database\Migrator;
use PDO;
use PHPUnit\Framework\TestCase;
use Throwable;

abstract class DatabaseTestCase extends TestCase
{
    private static ?PDO $database = null;

    public static function setUpBeforeClass(): void
    {
        $dsn = (string) (getenv('TEST_DATABASE_DSN') ?: 'pgsql:host=127.0.0.1;port=5432;dbname=backup_catalog_test');
        $user = (string) (getenv('TEST_DATABASE_USER') ?: 'backup_catalog');
        $password = (string) (getenv('TEST_DATABASE_PASSWORD') ?: 'backup_catalog_test');
        try {
            self::$database = ConnectionFactory::create($dsn, $user, $password);
        } catch (Throwable) {
            return;
        }
        new Migrator(self::$database, dirname(__DIR__, 2) . '/database/migrations')->migrate();
    }

    protected function setUp(): void
    {
        $this->pdo()->beginTransaction();
    }

    protected function tearDown(): void
    {
        if (null !== self::$database && self::$database->inTransaction()) {
            self::$database->rollBack();
        }
    }

    protected function pdo(): PDO
    {
        if (null === self::$database) {
            self::markTestSkipped('PostgreSQL integration database is unavailable.');
        }

        return self::$database;
    }
}
