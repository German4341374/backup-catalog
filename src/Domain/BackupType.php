<?php

declare(strict_types=1);

namespace BackupCatalog\Domain;

enum BackupType: string
{
    case Full = 'Full';
    case Incremental = 'Incremental';
    case Snapshot = 'Snapshot';
}
