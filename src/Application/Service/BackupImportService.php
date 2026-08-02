<?php

declare(strict_types=1);

namespace BackupCatalog\Application\Service;

use BackupCatalog\Application\Exception\ValidationException;
use BackupCatalog\Application\Validation\BackupRunValidator;
use BackupCatalog\Infrastructure\Repository\BackupJobRepository;
use BackupCatalog\Infrastructure\Repository\BackupRunRepository;
use BackupCatalog\Infrastructure\Repository\SystemRepository;
use JsonException;

final readonly class BackupImportService
{
    public function __construct(
        private SystemRepository $systems,
        private BackupJobRepository $jobs,
        private BackupRunRepository $runs,
        private BackupRunValidator $validator,
    ) {}

    /**
     * @return array{imported: int, skipped: int, total: int}
     */
    public function importJson(string $json): array
    {
        try {
            $decoded = json_decode($json, true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new ValidationException(['file' => 'The JSON document is invalid: ' . $exception->getMessage()]);
        }
        if (!is_array($decoded)) {
            throw new ValidationException(['file' => 'The JSON root must be an array or an object with a runs array.']);
        }
        $records = array_is_list($decoded) ? $decoded : ($decoded['runs'] ?? null);
        if (!is_array($records) || !array_is_list($records)) {
            throw new ValidationException(['runs' => 'The runs property must be a JSON array.']);
        }
        if (count($records) > 5000) {
            throw new ValidationException(['runs' => 'One import cannot contain more than 5000 runs.']);
        }

        $validated = [];
        $skipped = 0;
        $errors = [];
        $seenExternalIds = [];
        foreach ($records as $index => $record) {
            if (!is_array($record)) {
                $errors['runs.' . $index] = 'Each run must be a JSON object.';
                continue;
            }
            try {
                $systemName = trim((string) ($record['system'] ?? $record['systemName'] ?? ''));
                $system = $this->systems->findByName($systemName);
                if (null === $system) {
                    throw new ValidationException(['system' => sprintf('Unknown system "%s".', $systemName)]);
                }
                $jobName = trim((string) ($record['job'] ?? $record['jobName'] ?? ''));
                $job = '' === $jobName
                    ? $this->jobs->firstForSystem((int) $system['id'])
                    : $this->jobs->findBySystemAndName((int) $system['id'], $jobName);
                if (null === $job) {
                    throw new ValidationException(['job' => sprintf('No matching backup job for "%s".', $systemName)]);
                }

                $record['systemId'] = (int) $system['id'];
                $record['backupJobId'] = (int) $job['id'];
                $record['type'] ??= $job['backup_type'];
                $data = $this->validator->validate($record);
                if (null === $data['externalId']) {
                    throw new ValidationException(['externalId' => 'External ID is required for idempotent imports.']);
                }
                if (isset($seenExternalIds[$data['externalId']]) || $this->runs->externalIdExists($data['externalId'])) {
                    ++$skipped;
                    continue;
                }
                $seenExternalIds[$data['externalId']] = true;
                $validated[] = $data;
            } catch (ValidationException $exception) {
                foreach ($exception->errors() as $field => $message) {
                    $errors[sprintf('runs.%d.%s', $index, $field)] = $message;
                }
            }
        }

        if ([] !== $errors) {
            throw new ValidationException($errors);
        }

        $this->runs->transactional(function () use ($validated): void {
            foreach ($validated as $data) {
                $this->runs->create($data);
            }
        });

        return ['imported' => count($validated), 'skipped' => $skipped, 'total' => count($records)];
    }

    /** @return array{imported: int, skipped: int, total: int} */
    public function importFile(string $path): array
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new ValidationException(['file' => sprintf('File "%s" is not readable.', $path)]);
        }
        $size = filesize($path);
        if (false === $size || $size > 5 * 1024 * 1024) {
            throw new ValidationException(['file' => 'Import files must not exceed 5 MB.']);
        }
        $json = file_get_contents($path);
        if (false === $json) {
            throw new ValidationException(['file' => 'Unable to read the import file.']);
        }

        return $this->importJson($json);
    }
}
