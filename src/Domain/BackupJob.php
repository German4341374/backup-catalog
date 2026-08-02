<?php

declare(strict_types=1);

namespace BackupCatalog\Domain;

final readonly class BackupJob
{
    public function __construct(
        public int $id,
        public int $systemId,
        public string $name,
        public BackupType $type,
        public string $scheduleDescription,
        public int $maxRuntimeMinutes,
        public bool $enabled,
    ) {}
}
