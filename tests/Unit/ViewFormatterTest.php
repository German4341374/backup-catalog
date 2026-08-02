<?php

declare(strict_types=1);

namespace BackupCatalog\Tests\Unit;

use BackupCatalog\Presentation\View\ViewFormatter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ViewFormatterTest extends TestCase
{
    #[DataProvider('byteValues')]
    public function testBytesAreReadable(int|float|string|null $value, string $expected): void
    {
        self::assertSame($expected, ViewFormatter::bytes($value));
    }

    /** @return iterable<array{int|float|string|null, string}> */
    public static function byteValues(): iterable
    {
        yield [0, '0 B'];
        yield [1024, '1 KB'];
        yield [1073741824, '1.0 GB'];
        yield [null, '-'];
    }

    public function testDurationUsesCompletionTime(): void
    {
        self::assertSame('1h 35m', ViewFormatter::duration('2026-08-01T10:00:00Z', '2026-08-01T11:35:00Z'));
    }
}
