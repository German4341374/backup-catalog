<?php

declare(strict_types=1);

namespace BackupCatalog\Application\Service;

use BackupCatalog\Infrastructure\Repository\BackupRunRepository;
use RuntimeException;

final readonly class CsvExportService
{
    public function __construct(private BackupRunRepository $runs) {}

    /** @param array{systemId?: int, status?: string, type?: string, from?: string, to?: string} $filters */
    public function export(array $filters): string
    {
        $result = $this->runs->paginate($filters, 0, 5000);
        $stream = fopen('php://temp', 'w+');
        if (false === $stream) {
            throw new RuntimeException('Unable to create CSV export.');
        }
        fputcsv($stream, [
            'id',
            'system',
            'job',
            'type',
            'status',
            'started_at',
            'completed_at',
            'size_bytes',
            'storage_location',
            'error_message',
            'external_id',
        ], escape: '\\');
        foreach ($result['items'] as $run) {
            fputcsv($stream, [
                $run['id'],
                $run['system_name'],
                $run['job_name'],
                $run['backup_type'],
                $run['status'],
                $run['started_at'],
                $run['completed_at'],
                $run['size_bytes'],
                $run['storage_location'],
                $run['error_message'],
                $run['external_id'],
            ], escape: '\\');
        }
        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);
        if (false === $csv) {
            throw new RuntimeException('Unable to read CSV export.');
        }

        return $csv;
    }
}
