<?php

declare(strict_types=1);

namespace BackupCatalog\Tests\Unit;

use BackupCatalog\Application\Exception\ValidationException;
use BackupCatalog\Application\Validation\BackupRunValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class BackupRunValidatorTest extends TestCase
{
    public function testSuccessfulRunIsNormalisedToUtc(): void
    {
        $result = new BackupRunValidator()->validate($this->valid());

        self::assertSame('2026-08-01T22:00:00+00:00', $result['startedAt']);
        self::assertSame('2026-08-01T22:30:00+00:00', $result['completedAt']);
        self::assertSame(1024, $result['sizeBytes']);
    }

    public function testRunningRunMayOmitCompletion(): void
    {
        $result = new BackupRunValidator()->validate([
            ...$this->valid(),
            'status' => 'Running',
            'completedAt' => '',
        ]);

        self::assertNull($result['completedAt']);
    }

    /** @param array<string, mixed> $changes */
    #[DataProvider('invalidRuns')]
    public function testInvalidRunIsRejected(array $changes, string $field): void
    {
        try {
            new BackupRunValidator()->validate([...$this->valid(), ...$changes]);
            self::fail('Expected validation to fail.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey($field, $exception->errors());
        }
    }

    /** @return iterable<string, array{array<string, mixed>, string}> */
    public static function invalidRuns(): iterable
    {
        yield 'unknown system' => [['systemId' => 0], 'systemId'];
        yield 'unknown type' => [['type' => 'Mirror'], 'type'];
        yield 'unknown status' => [['status' => 'Done'], 'status'];
        yield 'bad start timestamp' => [['startedAt' => 'not-a-date'], 'startedAt'];
        yield 'bad completion timestamp' => [['completedAt' => 'not-a-date'], 'completedAt'];
        yield 'completion before start' => [['completedAt' => '2026-08-01T23:00:00+02:00'], 'completedAt'];
        yield 'completed missing' => [['completedAt' => ''], 'completedAt'];
        yield 'running completed' => [['status' => 'Running'], 'completedAt'];
        yield 'negative size' => [['sizeBytes' => -1], 'sizeBytes'];
        yield 'missing storage' => [['storageLocation' => ''], 'storageLocation'];
    }

    /** @return array<string, mixed> */
    private function valid(): array
    {
        return [
            'systemId' => 1,
            'backupJobId' => 1,
            'externalId' => 'test-001',
            'type' => 'Full',
            'startedAt' => '2026-08-02T00:00:00+02:00',
            'completedAt' => '2026-08-02T00:30:00+02:00',
            'status' => 'Successful',
            'sizeBytes' => '1024',
            'storageLocation' => 'vault://test/system',
            'errorMessage' => '',
        ];
    }
}
