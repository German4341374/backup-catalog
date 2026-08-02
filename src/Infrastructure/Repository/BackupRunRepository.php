<?php

declare(strict_types=1);

namespace BackupCatalog\Infrastructure\Repository;

use BackupCatalog\Application\Exception\NotFoundException;
use PDO;
use PDOStatement;

final readonly class BackupRunRepository
{
    public function __construct(private PDO $pdo) {}

    /**
     * @param array{systemId?: int, status?: string, type?: string, from?: string, to?: string} $filters
     * @return array{items: list<array<string, mixed>>, total: int, page: int, size: int, pages: int}
     */
    public function paginate(array $filters, int $page, int $size): array
    {
        [$where, $parameters] = $this->buildFilter($filters);
        $base = ' FROM backup_runs r JOIN systems s ON s.id = r.system_id '
            . 'LEFT JOIN backup_jobs j ON j.id = r.backup_job_id ';
        $statement = $this->pdo->prepare(
            'SELECT r.*, s.name AS system_name, j.name AS job_name ' . $base . $where
            . ' ORDER BY r.started_at DESC LIMIT :limit OFFSET :offset',
        );
        $this->bindParameters($statement, $parameters);
        $statement->bindValue('limit', $size, PDO::PARAM_INT);
        $statement->bindValue('offset', $page * $size, PDO::PARAM_INT);
        $statement->execute();
        $items = $statement->fetchAll();

        $count = $this->pdo->prepare('SELECT COUNT(*) ' . $base . $where);
        $this->bindParameters($count, $parameters);
        $count->execute();
        $total = (int) $count->fetchColumn();

        return [
            'items' => array_values(array_map($this->normalise(...), $items)),
            'total' => $total,
            'page' => $page,
            'size' => $size,
            'pages' => 0 === $total ? 0 : (int) ceil($total / $size),
        ];
    }

    /** @return array<string, mixed> */
    public function find(int $id): array
    {
        $statement = $this->pdo->prepare(
            'SELECT r.*, s.name AS system_name, j.name AS job_name FROM backup_runs r '
            . 'JOIN systems s ON s.id = r.system_id LEFT JOIN backup_jobs j ON j.id = r.backup_job_id '
            . 'WHERE r.id = :id',
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();
        if (false === $row) {
            throw new NotFoundException(sprintf('Backup run %d was not found.', $id));
        }

        return $this->normalise($row);
    }

    /**
     * @param array{systemId: int, backupJobId: ?int, externalId: ?string, type: string, startedAt: string, completedAt: ?string, status: string, sizeBytes: ?int, storageLocation: string, errorMessage: ?string} $data
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO backup_runs (system_id, backup_job_id, external_id, backup_type, started_at, '
            . 'completed_at, status, size_bytes, storage_location, error_message) VALUES '
            . '(:system_id, :job_id, :external_id, :backup_type, :started_at, :completed_at, '
            . ':status, :size_bytes, :storage_location, :error_message) RETURNING id',
        );
        $statement->execute([
            'system_id' => $data['systemId'],
            'job_id' => $data['backupJobId'],
            'external_id' => $data['externalId'],
            'backup_type' => $data['type'],
            'started_at' => $data['startedAt'],
            'completed_at' => $data['completedAt'],
            'status' => $data['status'],
            'size_bytes' => $data['sizeBytes'],
            'storage_location' => $data['storageLocation'],
            'error_message' => $data['errorMessage'],
        ]);

        return $this->find((int) $statement->fetchColumn());
    }

    public function externalIdExists(string $externalId): bool
    {
        $statement = $this->pdo->prepare('SELECT EXISTS(SELECT 1 FROM backup_runs WHERE external_id = :external_id)');
        $statement->execute(['external_id' => $externalId]);

        $value = $statement->fetchColumn();

        return true === $value || 1 === $value || '1' === $value || 't' === $value;
    }

    /**
     * @template T
     * @param callable(): T $operation
     * @return T
     */
    public function transactional(callable $operation): mixed
    {
        $this->pdo->beginTransaction();
        try {
            $result = $operation();
            $this->pdo->commit();

            return $result;
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    /**
     * @param array{systemId?: int, status?: string, type?: string, from?: string, to?: string} $filters
     * @return array{0: string, 1: array<string, int|string>}
     */
    private function buildFilter(array $filters): array
    {
        $clauses = [];
        $parameters = [];
        if (isset($filters['systemId']) && $filters['systemId'] > 0) {
            $clauses[] = 'r.system_id = :system_id';
            $parameters['system_id'] = $filters['systemId'];
        }
        if (isset($filters['status']) && '' !== $filters['status']) {
            $clauses[] = 'r.status = :status';
            $parameters['status'] = $filters['status'];
        }
        if (isset($filters['type']) && '' !== $filters['type']) {
            $clauses[] = 'r.backup_type = :backup_type';
            $parameters['backup_type'] = $filters['type'];
        }
        if (isset($filters['from']) && '' !== $filters['from']) {
            $clauses[] = 'r.started_at >= :started_from';
            $parameters['started_from'] = $filters['from'];
        }
        if (isset($filters['to']) && '' !== $filters['to']) {
            $clauses[] = 'r.started_at <= :started_to';
            $parameters['started_to'] = $filters['to'];
        }

        return [empty($clauses) ? '' : 'WHERE ' . implode(' AND ', $clauses), $parameters];
    }

    /** @param array<string, int|string> $parameters */
    private function bindParameters(PDOStatement $statement, array $parameters): void
    {
        foreach ($parameters as $name => $value) {
            $statement->bindValue($name, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function normalise(array $row): array
    {
        foreach (['id', 'system_id', 'backup_job_id', 'size_bytes'] as $field) {
            if (isset($row[$field])) {
                $row[$field] = (int) $row[$field];
            }
        }

        return $row;
    }
}
