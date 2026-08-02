<?php

declare(strict_types=1);

namespace BackupCatalog\Domain;

final readonly class BackupRun
{
    public function __construct(
        public int $id,
        public int $systemId,
        public ?int $backupJobId,
        public BackupType $type,
        public string $startedAt,
        public ?string $completedAt,
        public BackupStatus $status,
        public ?int $sizeBytes,
        public string $storageLocation,
        public ?string $errorMessage,
        public ?string $externalId,
    ) {}
}
