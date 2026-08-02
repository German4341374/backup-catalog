<?php

declare(strict_types=1);

namespace BackupCatalog\Infrastructure\Repository;

use BackupCatalog\Application\Exception\NotFoundException;
use PDO;
use PDOStatement;

final readonly class SystemRepository
{
    public function __construct(private PDO $pdo) {}

    /**
     * @param array{criticality?: string, health?: string, query?: string} $filters
     * @return array{items: list<array<string, mixed>>, total: int, page: int, size: int, pages: int}
     */
    public function paginate(array $filters, int $page, int $size): array
    {
        [$where, $parameters] = $this->buildFilter($filters);
        $offset = $page * $size;
        $sql = 'SELECT * FROM system_backup_health ' . $where
            . " ORDER BY CASE criticality WHEN 'Critical' THEN 1 WHEN 'High' THEN 2 "
            . "WHEN 'Medium' THEN 3 ELSE 4 END, name LIMIT :limit OFFSET :offset";
        $statement = $this->pdo->prepare($sql);
        $this->bindParameters($statement, $parameters);
        $statement->bindValue('limit', $size, PDO::PARAM_INT);
        $statement->bindValue('offset', $offset, PDO::PARAM_INT);
        $statement->execute();
        $items = $statement->fetchAll();

        $countStatement = $this->pdo->prepare('SELECT COUNT(*) FROM system_backup_health ' . $where);
        $this->bindParameters($countStatement, $parameters);
        $countStatement->execute();
        $total = (int) $countStatement->fetchColumn();

        return [
            'items' => array_values(array_map($this->normalise(...), $items)),
            'total' => $total,
            'page' => $page,
            'size' => $size,
            'pages' => 0 === $total ? 0 : (int) ceil($total / $size),
        ];
    }

    /** @return list<array<string, mixed>> */
    public function all(): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM systems ORDER BY name');
        $statement->execute();
        $rows = $statement->fetchAll();

        return array_values(array_map($this->normalise(...), $rows));
    }

    /** @return array<string, mixed> */
    public function find(int $id): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM system_backup_health WHERE id = :id');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();
        if (false === $row) {
            throw new NotFoundException(sprintf('System %d was not found.', $id));
        }

        return $this->normalise($row);
    }

    /** @return array<string, mixed>|null */
    public function findByName(string $name): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM systems WHERE lower(name) = lower(:name)');
        $statement->execute(['name' => $name]);
        $row = $statement->fetch();

        return false === $row ? null : $this->normalise($row);
    }

    /**
     * @param array{name: string, owner: string, criticality: string, expectedBackupIntervalHours: int} $data
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO systems (name, owner, criticality, expected_backup_interval_hours) '
            . 'VALUES (:name, :owner, :criticality, :interval) RETURNING id',
        );
        $statement->execute([
            'name' => $data['name'],
            'owner' => $data['owner'],
            'criticality' => $data['criticality'],
            'interval' => $data['expectedBackupIntervalHours'],
        ]);

        return $this->find((int) $statement->fetchColumn());
    }

    /**
     * @param array{name: string, owner: string, criticality: string, expectedBackupIntervalHours: int} $data
     * @return array<string, mixed>
     */
    public function update(int $id, array $data): array
    {
        $this->find($id);
        $statement = $this->pdo->prepare(
            'UPDATE systems SET name = :name, owner = :owner, criticality = :criticality, '
            . 'expected_backup_interval_hours = :interval WHERE id = :id',
        );
        $statement->execute([
            'id' => $id,
            'name' => $data['name'],
            'owner' => $data['owner'],
            'criticality' => $data['criticality'],
            'interval' => $data['expectedBackupIntervalHours'],
        ]);

        return $this->find($id);
    }

    public function delete(int $id): void
    {
        $this->find($id);
        $statement = $this->pdo->prepare('DELETE FROM systems WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    /**
     * @param array{criticality?: string, health?: string, query?: string} $filters
     * @return array{0: string, 1: array<string, string>}
     */
    private function buildFilter(array $filters): array
    {
        $clauses = [];
        $parameters = [];
        if (isset($filters['criticality']) && '' !== $filters['criticality']) {
            $clauses[] = 'criticality = :criticality';
            $parameters['criticality'] = $filters['criticality'];
        }
        if (isset($filters['health']) && '' !== $filters['health']) {
            $clauses[] = 'health_status = :health';
            $parameters['health'] = $filters['health'];
        }
        if (isset($filters['query']) && '' !== trim($filters['query'])) {
            $clauses[] = '(name ILIKE :query_name OR owner ILIKE :query_owner)';
            $parameters['query_name'] = '%' . trim($filters['query']) . '%';
            $parameters['query_owner'] = '%' . trim($filters['query']) . '%';
        }

        return [empty($clauses) ? '' : 'WHERE ' . implode(' AND ', $clauses), $parameters];
    }

    /** @param array<string, string> $parameters */
    private function bindParameters(PDOStatement $statement, array $parameters): void
    {
        foreach ($parameters as $name => $value) {
            $statement->bindValue($name, $value);
        }
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function normalise(array $row): array
    {
        foreach (['id', 'expected_backup_interval_hours', 'last_successful_size_bytes'] as $field) {
            if (isset($row[$field])) {
                $row[$field] = (int) $row[$field];
            }
        }

        return $row;
    }
}
