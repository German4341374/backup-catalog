<?php

declare(strict_types=1);

namespace BackupCatalog\Presentation\Http;

final class Flash
{
    public function set(string $type, string $message): void
    {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }

    /** @return array{type: string, message: string}|null */
    public function consume(): ?array
    {
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        return is_array($flash) && isset($flash['type'], $flash['message'])
            ? ['type' => (string) $flash['type'], 'message' => (string) $flash['message']]
            : null;
    }
}
