<?php

declare(strict_types=1);

namespace BackupCatalog\Presentation\Http;

use BackupCatalog\Application\Exception\ValidationException;
use Psr\Http\Message\ServerRequestInterface;

final class RequestData
{
    /** @return array<string, mixed> */
    public static function body(ServerRequestInterface $request): array
    {
        $body = $request->getParsedBody();
        if (!is_array($body)) {
            throw new ValidationException(['body' => 'The request body must be a JSON object or form data.']);
        }

        return $body;
    }

    public static function page(ServerRequestInterface $request): int
    {
        $page = filter_var($request->getQueryParams()['page'] ?? 1, FILTER_VALIDATE_INT);

        return false === $page ? 0 : max(0, $page - 1);
    }

    public static function size(ServerRequestInterface $request, int $default = 20): int
    {
        $size = filter_var($request->getQueryParams()['size'] ?? $default, FILTER_VALIDATE_INT);

        return false === $size ? $default : max(1, min(100, $size));
    }
}
