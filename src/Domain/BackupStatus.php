<?php

declare(strict_types=1);

namespace BackupCatalog\Domain;

enum BackupStatus: string
{
    case Running = 'Running';
    case Successful = 'Successful';
    case Failed = 'Failed';
    case Cancelled = 'Cancelled';
}
