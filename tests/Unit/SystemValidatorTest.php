<?php

declare(strict_types=1);

namespace BackupCatalog\Tests\Unit;

use BackupCatalog\Application\Exception\ValidationException;
use BackupCatalog\Application\Validation\SystemValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SystemValidatorTest extends TestCase
{
    public function testValidSystemIsNormalised(): void
    {
        $result = new SystemValidator()->validate([
            'name' => '  Customer Portal  ',
            'owner' => ' Digital Operations ',
            'criticality' => 'Critical',
            'expectedBackupIntervalHours' => '12',
        ]);

        self::assertSame('Customer Portal', $result['name']);
        self::assertSame('Digital Operations', $result['owner']);
        self::assertSame(12, $result['expectedBackupIntervalHours']);
    }

    /** @param array<string, mixed> $input */
    #[DataProvider('invalidSystems')]
    public function testInvalidSystemIsRejected(array $input, string $field): void
    {
        try {
            new SystemValidator()->validate($input);
            self::fail('Expected validation to fail.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey($field, $exception->errors());
        }
    }

    /** @return iterable<string, array{array<string, mixed>, string}> */
    public static function invalidSystems(): iterable
    {
        $valid = ['name' => 'Portal', 'owner' => 'Operations', 'criticality' => 'High', 'expectedBackupIntervalHours' => 24];
        yield 'blank name' => [[...$valid, 'name' => ''], 'name'];
        yield 'blank owner' => [[...$valid, 'owner' => ''], 'owner'];
        yield 'unknown criticality' => [[...$valid, 'criticality' => 'Urgent'], 'criticality'];
        yield 'zero interval' => [[...$valid, 'expectedBackupIntervalHours' => 0], 'expectedBackupIntervalHours'];
        yield 'excessive interval' => [[...$valid, 'expectedBackupIntervalHours' => 9000], 'expectedBackupIntervalHours'];
    }
}
