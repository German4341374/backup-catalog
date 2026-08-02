<?php

declare(strict_types=1);

namespace BackupCatalog\Infrastructure\Database;

use PDO;
use RuntimeException;

final readonly class Migrator
{
    public function __construct(private PDO $pdo, private string $migrationDirectory) {}

    /** @return list<string> */
    public function migrate(): array
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS schema_migrations ('
            . 'filename TEXT PRIMARY KEY, checksum CHAR(64) NOT NULL, applied_at TIMESTAMPTZ NOT NULL DEFAULT NOW())',
        );

        $files = glob($this->migrationDirectory . '/*.sql');
        if (false === $files) {
            throw new RuntimeException('Unable to read migration directory.');
        }
        sort($files, SORT_STRING);

        $applied = [];
        $lookup = $this->pdo->prepare('SELECT checksum FROM schema_migrations WHERE filename = :filename');
        $record = $this->pdo->prepare(
            'INSERT INTO schema_migrations (filename, checksum) VALUES (:filename, :checksum)',
        );

        foreach ($files as $file) {
            $filename = basename($file);
            $checksum = hash_file('sha256', $file);
            if (false === $checksum) {
                throw new RuntimeException(sprintf('Unable to hash migration %s.', $filename));
            }

            $lookup->execute(['filename' => $filename]);
            $existing = $lookup->fetchColumn();
            if (false !== $existing) {
                if (!hash_equals((string) $existing, $checksum)) {
                    throw new RuntimeException(sprintf('Applied migration %s was modified.', $filename));
                }
                continue;
            }

            $sql = file_get_contents($file);
            if (false === $sql) {
                throw new RuntimeException(sprintf('Unable to read migration %s.', $filename));
            }

            $this->pdo->beginTransaction();
            try {
                $this->pdo->exec($sql);
                $record->execute(['filename' => $filename, 'checksum' => $checksum]);
                $this->pdo->commit();
                $applied[] = $filename;
            } catch (\Throwable $exception) {
                $this->pdo->rollBack();
                throw $exception;
            }
        }

        return $applied;
    }
}
