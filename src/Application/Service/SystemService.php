<?php

declare(strict_types=1);

namespace BackupCatalog\Application\Service;

use BackupCatalog\Application\Validation\SystemValidator;
use BackupCatalog\Infrastructure\Repository\SystemRepository;

final readonly class SystemService
{
    public function __construct(
        private SystemRepository $systems,
        private SystemValidator $validator,
    ) {}

    /** @param array<string, mixed> $input
     *  @return array<string, mixed>
     */
    public function create(array $input): array
    {
        return $this->systems->create($this->validator->validate($input));
    }

    /** @param array<string, mixed> $input
     *  @return array<string, mixed>
     */
    public function update(int $id, array $input): array
    {
        return $this->systems->update($id, $this->validator->validate($input));
    }
}
