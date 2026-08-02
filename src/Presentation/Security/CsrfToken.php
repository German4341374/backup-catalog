<?php

declare(strict_types=1);

namespace BackupCatalog\Presentation\Security;

final class CsrfToken
{
    public function value(): string
    {
        if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    public function isValid(string $candidate): bool
    {
        return '' !== $candidate && hash_equals($this->value(), $candidate);
    }
}
