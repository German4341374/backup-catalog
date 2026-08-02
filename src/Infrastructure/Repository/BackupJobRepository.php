<?php

declare(strict_types=1);

namespace BackupCatalog\Infrastructure\Repository;

use PDO;

final readonly class BackupJobRepository
{
    public function __construct(private PDO $pdo) {}

    /** @return list<array<string, mixed>> */
    public function forSystem(int $systemId): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM backup_jobs WHERE system_id = :system_id ORDER BY name');
        $statement->execute(['system_id' => $systemId]);

        return array_values(array_map($this->normalise(...), $statement->fetchAll()));
    }

    /** @return list<array<string, mixed>> */
    public function allEnabled(): array
    {
        $statement = $this->pdo->prepare(
            'SELECT j.*, s.name AS system_name FROM backup_jobs j '
            . 'JOIN systems s ON s.id = j.system_id WHERE j.enabled = TRUE ORDER BY s.name, j.name',
        );
        $statement->execute();
        $rows = $statement->fetchAll();

        return array_values(array_map($this->normalise(...), $rows));
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM backup_jobs WHERE id = :id');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return false === $row ? null : $this->normalise($row);
    }

    /** @return array<string, mixed>|null */
    public function findBySystemAndName(int $systemId, string $name): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM backup_jobs WHERE system_id = :system_id AND lower(name) = lower(:name)',
        );
        $statement->execute(['system_id' => $systemId, 'name' => $name]);
        $row = $statement->fetch();

        return false === $row ? null : $this->normalise($row);
    }

    /** @return array<string, mixed>|null */
    public function firstForSystem(int $systemId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM backup_jobs WHERE system_id = :system_id AND enabled = TRUE ORDER BY id LIMIT 1',
        );
        $statement->execute(['system_id' => $systemId]);
        $row = $statement->fetch();

        return false === $row ? null : $this->normalise($row);
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function normalise(array $row): array
    {
        $row['id'] = (int) $row['id'];
        $row['system_id'] = (int) $row['system_id'];
        $row['max_runtime_minutes'] = (int) $row['max_runtime_minutes'];
        $row['enabled'] = filter_var($row['enabled'], FILTER_VALIDATE_BOOL);

        return $row;
    }
}
