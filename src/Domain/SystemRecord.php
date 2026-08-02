<?php

declare(strict_types=1);

namespace BackupCatalog\Domain;

final readonly class SystemRecord
{
    public function __construct(
        public int $id,
        public string $name,
        public string $owner,
        public Criticality $criticality,
        public int $expectedBackupIntervalHours,
        public string $createdAt,
        public string $updatedAt,
    ) {}
}
