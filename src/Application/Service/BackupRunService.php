<?php

declare(strict_types=1);

namespace BackupCatalog\Application\Service;

use BackupCatalog\Application\Exception\ValidationException;
use BackupCatalog\Application\Validation\BackupRunValidator;
use BackupCatalog\Infrastructure\Repository\BackupJobRepository;
use BackupCatalog\Infrastructure\Repository\BackupRunRepository;
use BackupCatalog\Infrastructure\Repository\SystemRepository;

final readonly class BackupRunService
{
    public function __construct(
        private BackupRunRepository $runs,
        private SystemRepository $systems,
        private BackupJobRepository $jobs,
        private BackupRunValidator $validator,
    ) {}

    /** @param array<string, mixed> $input
     *  @return array<string, mixed>
     */
    public function register(array $input): array
    {
        $data = $this->validator->validate($input);
        $this->systems->find($data['systemId']);
        if (null !== $data['backupJobId']) {
            $job = $this->jobs->find($data['backupJobId']);
            if (null === $job || (int) $job['system_id'] !== $data['systemId']) {
                throw new ValidationException(['backupJobId' => 'Backup job must belong to the selected system.']);
            }
        }

        return $this->runs->create($data);
    }
}
