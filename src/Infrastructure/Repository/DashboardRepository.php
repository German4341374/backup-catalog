<?php

declare(strict_types=1);

namespace BackupCatalog\Infrastructure\Repository;

use PDO;

final readonly class DashboardRepository
{
    public function __construct(private PDO $pdo) {}

    /** @return array<string, int|float> */
    public function summary(int $fallbackOverdueHours): array
    {
        $statement = $this->pdo->prepare(
            "SELECT
                (SELECT COUNT(*) FROM systems) AS total_systems,
                (SELECT COUNT(*) FROM system_backup_health WHERE health_status = 'Healthy') AS healthy_systems,
                (SELECT COUNT(*) FROM system_backup_health WHERE health_status <> 'Healthy') AS overdue_systems,
                (SELECT COUNT(*) FROM backup_runs WHERE status = 'Failed'
                    AND started_at >= NOW() - INTERVAL '24 hours') AS failed_24h,
                (SELECT COUNT(*) FROM backup_runs r LEFT JOIN backup_jobs j ON j.id = r.backup_job_id
                    WHERE r.status = 'Running'
                    AND r.started_at < NOW() -
                        (COALESCE(j.max_runtime_minutes, :fallback_minutes) * INTERVAL '1 minute')) AS overdue_running,
                (SELECT COALESCE(AVG(size_bytes), 0) FROM backup_runs
                    WHERE status = 'Successful'
                    AND completed_at >= NOW() - INTERVAL '30 days') AS average_size_bytes",
        );
        $statement->execute(['fallback_minutes' => $fallbackOverdueHours * 60]);
        /** @var array<string, int|float|string> $row */
        $row = $statement->fetch();

        return [
            'total_systems' => (int) $row['total_systems'],
            'healthy_systems' => (int) $row['healthy_systems'],
            'overdue_systems' => (int) $row['overdue_systems'],
            'failed_24h' => (int) $row['failed_24h'],
            'overdue_running' => (int) $row['overdue_running'],
            'average_size_bytes' => (float) $row['average_size_bytes'],
        ];
    }

    /** @return list<array<string, mixed>> */
    public function overdueSystems(int $limit = 8): array
    {
        $statement = $this->pdo->prepare(
            "SELECT * FROM system_backup_health WHERE health_status <> 'Healthy' "
            . "ORDER BY CASE criticality WHEN 'Critical' THEN 1 WHEN 'High' THEN 2 ELSE 3 END, name LIMIT :limit",
        );
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return array_values($statement->fetchAll());
    }

    /** @return list<array<string, mixed>> */
    public function recentFailures(int $limit = 8): array
    {
        $statement = $this->pdo->prepare(
            "SELECT r.*, s.name AS system_name FROM backup_runs r JOIN systems s ON s.id = r.system_id "
            . "WHERE r.status = 'Failed' ORDER BY r.started_at DESC LIMIT :limit",
        );
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return array_values($statement->fetchAll());
    }

    /** @return list<array{status: string, count: int}> */
    public function statusDistribution(): array
    {
        $statement = $this->pdo->prepare(
            "SELECT status, COUNT(*) AS count FROM backup_runs "
            . "WHERE started_at >= NOW() - INTERVAL '7 days' GROUP BY status ORDER BY status",
        );
        $statement->execute();
        $rows = $statement->fetchAll();

        return array_values(array_map(
            static fn(array $row): array => ['status' => (string) $row['status'], 'count' => (int) $row['count']],
            $rows,
        ));
    }

    /** @return list<array<string, mixed>> */
    public function overdueRunning(int $fallbackOverdueHours): array
    {
        $statement = $this->pdo->prepare(
            'SELECT r.*, s.name AS system_name, j.name AS job_name, '
            . 'COALESCE(j.max_runtime_minutes, :fallback_select) AS expected_minutes '
            . 'FROM backup_runs r JOIN systems s ON s.id = r.system_id '
            . 'LEFT JOIN backup_jobs j ON j.id = r.backup_job_id '
            . "WHERE r.status = 'Running' AND r.started_at < NOW() - "
            . "(COALESCE(j.max_runtime_minutes, :fallback_where) * INTERVAL '1 minute') "
            . 'ORDER BY r.started_at',
        );
        $minutes = $fallbackOverdueHours * 60;
        $statement->execute(['fallback_select' => $minutes, 'fallback_where' => $minutes]);

        return array_values($statement->fetchAll());
    }
}
