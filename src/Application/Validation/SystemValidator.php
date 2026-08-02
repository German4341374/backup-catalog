<?php

declare(strict_types=1);

namespace BackupCatalog\Application\Validation;

use BackupCatalog\Application\Exception\ValidationException;
use BackupCatalog\Domain\Criticality;

final class SystemValidator
{
    /**
     * @param array<string, mixed> $input
     * @return array{name: string, owner: string, criticality: string, expectedBackupIntervalHours: int}
     */
    public function validate(array $input): array
    {
        $name = trim((string) ($input['name'] ?? ''));
        $owner = trim((string) ($input['owner'] ?? ''));
        $criticality = trim((string) ($input['criticality'] ?? ''));
        $interval = filter_var(
            $input['expectedBackupIntervalHours'] ?? $input['expected_backup_interval_hours'] ?? null,
            FILTER_VALIDATE_INT,
        );

        $errors = [];
        if ('' === $name || mb_strlen($name) > 120) {
            $errors['name'] = 'Name is required and must not exceed 120 characters.';
        }
        if ('' === $owner || mb_strlen($owner) > 160) {
            $errors['owner'] = 'Owner is required and must not exceed 160 characters.';
        }
        if (null === Criticality::tryFrom($criticality)) {
            $errors['criticality'] = 'Criticality must be Low, Medium, High, or Critical.';
        }
        if (false === $interval || $interval < 1 || $interval > 8760) {
            $errors['expectedBackupIntervalHours'] = 'Expected interval must be between 1 and 8760 hours.';
        }
        if ([] !== $errors) {
            throw new ValidationException($errors);
        }

        return [
            'name' => $name,
            'owner' => $owner,
            'criticality' => $criticality,
            'expectedBackupIntervalHours' => (int) $interval,
        ];
    }
}
