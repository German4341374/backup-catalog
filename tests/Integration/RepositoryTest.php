<?php

declare(strict_types=1);

namespace BackupCatalog\Tests\Integration;

use BackupCatalog\Infrastructure\Repository\BackupJobRepository;
use BackupCatalog\Infrastructure\Repository\BackupRunRepository;
use BackupCatalog\Infrastructure\Repository\DashboardRepository;
use BackupCatalog\Infrastructure\Repository\SystemRepository;

final class RepositoryTest extends DatabaseTestCase
{
    public function testSystemCrudAndHealthProjection(): void
    {
        $repository = new SystemRepository($this->pdo());
        $created = $repository->create([
            'name' => 'Integration Test System',
            'owner' => 'Test Operations',
            'criticality' => 'Low',
            'expectedBackupIntervalHours' => 48,
        ]);

        self::assertSame('Never backed up', $created['health_status']);
        $updated = $repository->update((int) $created['id'], [
            'name' => 'Integration Test System',
            'owner' => 'Platform Test Team',
            'criticality' => 'Medium',
            'expectedBackupIntervalHours' => 24,
        ]);
        self::assertSame('Platform Test Team', $updated['owner']);
        self::assertSame('Integration Test System', $repository->findByName('integration test system')['name'] ?? null);
        $repository->delete((int) $created['id']);
        self::assertNull($repository->findByName('Integration Test System'));
    }

    public function testSystemFiltersUseHealthView(): void
    {
        $result = new SystemRepository($this->pdo())->paginate(['criticality' => 'Critical'], 0, 50);

        self::assertGreaterThanOrEqual(3, $result['total']);
        foreach ($result['items'] as $system) {
            self::assertIsArray($system);
            self::assertSame('Critical', $system['criticality']);
        }
    }

    public function testBackupRunCanBeCreatedAndFound(): void
    {
        $systems = new SystemRepository($this->pdo());
        $system = $systems->findByName('Customer CRM');
        self::assertNotNull($system);
        $job = new BackupJobRepository($this->pdo())->firstForSystem((int) $system['id']);
        self::assertNotNull($job);
        $runs = new BackupRunRepository($this->pdo());
        $externalId = 'integration-' . bin2hex(random_bytes(6));
        $created = $runs->create([
            'systemId' => (int) $system['id'],
            'backupJobId' => (int) $job['id'],
            'externalId' => $externalId,
            'type' => 'Full',
            'startedAt' => '2026-08-02T00:00:00Z',
            'completedAt' => '2026-08-02T00:10:00Z',
            'status' => 'Successful',
            'sizeBytes' => 2048,
            'storageLocation' => 'vault://integration/test',
            'errorMessage' => null,
        ]);

        self::assertSame($externalId, $created['external_id']);
        self::assertTrue($runs->externalIdExists($externalId));
        self::assertFalse($runs->externalIdExists('not-present-' . bin2hex(random_bytes(4))));
    }

    public function testDashboardCalculatesSummaryAndAlerts(): void
    {
        $dashboard = new DashboardRepository($this->pdo());
        $summary = $dashboard->summary(4);

        self::assertGreaterThanOrEqual(8, $summary['total_systems']);
        self::assertGreaterThan(0, $summary['average_size_bytes']);
        self::assertNotEmpty($dashboard->recentFailures());
        self::assertNotEmpty($dashboard->statusDistribution());
    }
}
