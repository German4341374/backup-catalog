<?php

declare(strict_types=1);

namespace BackupCatalog\Application\Service;

use BackupCatalog\Infrastructure\Repository\DashboardRepository;

final readonly class DashboardService
{
    public function __construct(
        private DashboardRepository $dashboard,
        private int $overdueRunningHours,
    ) {}

    /** @return array<string, mixed> */
    public function overview(): array
    {
        return [
            'summary' => $this->dashboard->summary($this->overdueRunningHours),
            'overdueSystems' => $this->dashboard->overdueSystems(),
            'recentFailures' => $this->dashboard->recentFailures(),
            'overdueRunning' => $this->dashboard->overdueRunning($this->overdueRunningHours),
            'statusDistribution' => $this->dashboard->statusDistribution(),
        ];
    }

    /** @return list<array<string, mixed>> */
    public function overdueRunning(): array
    {
        return $this->dashboard->overdueRunning($this->overdueRunningHours);
    }
}
