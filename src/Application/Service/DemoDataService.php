<?php

declare(strict_types=1);

namespace BackupCatalog\Application\Service;

use BackupCatalog\Infrastructure\Repository\BackupJobRepository;
use BackupCatalog\Infrastructure\Repository\BackupRunRepository;
use BackupCatalog\Infrastructure\Repository\SystemRepository;
use DateTimeImmutable;
use DateTimeZone;

final readonly class DemoDataService
{
    public function __construct(
        private SystemRepository $systems,
        private BackupJobRepository $jobs,
        private BackupRunRepository $runs,
    ) {}

    /** @return array{created: int, skipped: int} */
    public function generate(): array
    {
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $created = 0;
        $skipped = 0;

        foreach ($this->systems->all() as $index => $system) {
            $job = $this->jobs->firstForSystem((int) $system['id']);
            if (null === $job) {
                continue;
            }
            $externalId = sprintf('demo-cli-%s-%d', $now->format('Ymd'), (int) $system['id']);
            if ($this->runs->externalIdExists($externalId)) {
                ++$skipped;
                continue;
            }
            $startedAt = $now->modify(sprintf('-%d minutes', 30 + (int) $index * 7));
            $completedAt = $startedAt->modify(sprintf('+%d minutes', 12 + (int) $index));
            $this->runs->create([
                'systemId' => (int) $system['id'],
                'backupJobId' => (int) $job['id'],
                'externalId' => $externalId,
                'type' => (string) $job['backup_type'],
                'startedAt' => $startedAt->format(DATE_ATOM),
                'completedAt' => $completedAt->format(DATE_ATOM),
                'status' => 'Successful',
                'sizeBytes' => 5_000_000_000 + ((int) $index * 750_000_000),
                'storageLocation' => sprintf('s3://demo-generated/system-%d/%s', $system['id'], $now->format('Ymd')),
                'errorMessage' => null,
            ]);
            ++$created;
        }

        return ['created' => $created, 'skipped' => $skipped];
    }
}
