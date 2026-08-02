<?php

declare(strict_types=1);

namespace BackupCatalog\Presentation\View;

use DateTimeImmutable;
use DateTimeZone;
use Throwable;

final class ViewFormatter
{
    public static function bytes(int|float|string|null $bytes): string
    {
        if (null === $bytes || !is_numeric($bytes)) {
            return '-';
        }
        $value = (float) $bytes;
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $index = 0;
        while ($value >= 1024 && $index < count($units) - 1) {
            $value /= 1024;
            ++$index;
        }

        return sprintf($index > 1 ? '%.1f %s' : '%.0f %s', $value, $units[$index]);
    }

    public static function date(?string $value): string
    {
        if (null === $value || '' === $value) {
            return '-';
        }
        try {
            return new DateTimeImmutable($value)
                ->setTimezone(new DateTimeZone('UTC'))
                ->format('M j, Y H:i') . ' UTC';
        } catch (Throwable) {
            return '-';
        }
    }

    public static function relative(?string $value): string
    {
        if (null === $value || '' === $value) {
            return 'never';
        }
        try {
            $date = new DateTimeImmutable($value);
            $seconds = time() - $date->getTimestamp();
            $future = $seconds < 0;
            $seconds = abs($seconds);
            if ($seconds < 60) {
                $text = 'less than a minute';
            } elseif ($seconds < 3600) {
                $text = (int) floor($seconds / 60) . ' min';
            } elseif ($seconds < 86400) {
                $text = (int) floor($seconds / 3600) . ' hr';
            } else {
                $text = (int) floor($seconds / 86400) . ' days';
            }

            return $future ? 'in ' . $text : $text . ' ago';
        } catch (Throwable) {
            return 'unknown';
        }
    }

    public static function duration(string $startedAt, ?string $completedAt): string
    {
        try {
            $start = new DateTimeImmutable($startedAt);
            $end = null === $completedAt ? new DateTimeImmutable('now') : new DateTimeImmutable($completedAt);
            $seconds = max(0, $end->getTimestamp() - $start->getTimestamp());
            $hours = intdiv($seconds, 3600);
            $minutes = intdiv($seconds % 3600, 60);

            return $hours > 0 ? sprintf('%dh %02dm', $hours, $minutes) : sprintf('%dm', $minutes);
        } catch (Throwable) {
            return '-';
        }
    }
}
