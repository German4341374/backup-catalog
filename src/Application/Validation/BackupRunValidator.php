<?php

declare(strict_types=1);

namespace BackupCatalog\Application\Validation;

use BackupCatalog\Application\Exception\ValidationException;
use BackupCatalog\Domain\BackupStatus;
use BackupCatalog\Domain\BackupType;
use DateTimeImmutable;
use DateTimeZone;
use Throwable;

final class BackupRunValidator
{
    /**
     * @param array<string, mixed> $input
     * @return array{systemId: int, backupJobId: ?int, externalId: ?string, type: string, startedAt: string, completedAt: ?string, status: string, sizeBytes: ?int, storageLocation: string, errorMessage: ?string}
     */
    public function validate(array $input): array
    {
        $systemId = filter_var($input['systemId'] ?? $input['system_id'] ?? null, FILTER_VALIDATE_INT);
        $jobValue = $input['backupJobId'] ?? $input['backup_job_id'] ?? null;
        $jobId = null === $jobValue || '' === $jobValue ? null : filter_var($jobValue, FILTER_VALIDATE_INT);
        $type = trim((string) ($input['type'] ?? $input['backup_type'] ?? ''));
        $status = trim((string) ($input['status'] ?? ''));
        $startedAt = $this->parseDate($input['startedAt'] ?? $input['started_at'] ?? null);
        $completedValue = $input['completedAt'] ?? $input['completed_at'] ?? null;
        $completedAt = $this->parseDate($completedValue, true);
        $sizeValue = $input['sizeBytes'] ?? $input['size_bytes'] ?? null;
        $sizeBytes = null === $sizeValue || '' === $sizeValue
            ? null
            : filter_var($sizeValue, FILTER_VALIDATE_INT);
        $storageLocation = trim((string) ($input['storageLocation'] ?? $input['storage_location'] ?? ''));
        $errorMessage = trim((string) ($input['errorMessage'] ?? $input['error_message'] ?? ''));
        $externalId = trim((string) ($input['externalId'] ?? $input['external_id'] ?? ''));

        $errors = [];
        if (false === $systemId || $systemId < 1) {
            $errors['systemId'] = 'A valid system is required.';
        }
        if (false === $jobId || (is_int($jobId) && $jobId < 1)) {
            $errors['backupJobId'] = 'Backup job must be a positive integer.';
        }
        if (null === BackupType::tryFrom($type)) {
            $errors['type'] = 'Type must be Full, Incremental, or Snapshot.';
        }
        if (null === BackupStatus::tryFrom($status)) {
            $errors['status'] = 'Status must be Running, Successful, Failed, or Cancelled.';
        }
        if (null === $startedAt) {
            $errors['startedAt'] = 'startedAt must be a valid date and time.';
        }
        if (null !== $completedValue && '' !== trim((string) $completedValue) && null === $completedAt) {
            $errors['completedAt'] = 'completedAt must be a valid date and time.';
        }
        if ('Running' === $status && null !== $completedAt) {
            $errors['completedAt'] = 'Running backups cannot have a completion time.';
        }
        if ('Running' !== $status && null === $completedAt) {
            $errors['completedAt'] = 'Completed, failed, and cancelled backups require a completion time.';
        }
        if (null !== $startedAt && null !== $completedAt && $completedAt < $startedAt) {
            $errors['completedAt'] = 'Completion time cannot be earlier than start time.';
        }
        if (false === $sizeBytes || (is_int($sizeBytes) && $sizeBytes < 0)) {
            $errors['sizeBytes'] = 'Size must be a non-negative integer.';
        }
        if ('' === $storageLocation || mb_strlen($storageLocation) > 500) {
            $errors['storageLocation'] = 'Storage location is required and must not exceed 500 characters.';
        }
        if (mb_strlen($errorMessage) > 2000) {
            $errors['errorMessage'] = 'Error message must not exceed 2000 characters.';
        }
        if (mb_strlen($externalId) > 160) {
            $errors['externalId'] = 'External ID must not exceed 160 characters.';
        }
        if ([] !== $errors) {
            throw new ValidationException($errors);
        }

        return [
            'systemId' => (int) $systemId,
            'backupJobId' => is_int($jobId) ? $jobId : null,
            'externalId' => '' === $externalId ? null : $externalId,
            'type' => $type,
            'startedAt' => $startedAt?->format(DATE_ATOM) ?? '',
            'completedAt' => $completedAt?->format(DATE_ATOM),
            'status' => $status,
            'sizeBytes' => is_int($sizeBytes) ? $sizeBytes : null,
            'storageLocation' => $storageLocation,
            'errorMessage' => '' === $errorMessage ? null : $errorMessage,
        ];
    }

    private function parseDate(mixed $value, bool $nullable = false): ?DateTimeImmutable
    {
        if ((null === $value || '' === trim((string) $value)) && $nullable) {
            return null;
        }
        try {
            return new DateTimeImmutable((string) $value)->setTimezone(new DateTimeZone('UTC'));
        } catch (Throwable) {
            return null;
        }
    }
}
