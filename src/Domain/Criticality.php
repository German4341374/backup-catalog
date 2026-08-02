<?php

declare(strict_types=1);

namespace BackupCatalog\Domain;

enum Criticality: string
{
    case Low = 'Low';
    case Medium = 'Medium';
    case High = 'High';
    case Critical = 'Critical';
}
