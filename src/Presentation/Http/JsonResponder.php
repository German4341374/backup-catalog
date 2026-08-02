<?php

declare(strict_types=1);

namespace BackupCatalog\Presentation\Http;

use JsonException;
use Psr\Http\Message\ResponseInterface;

final class JsonResponder
{
    /** @param array<string, mixed>|list<mixed> $data */
    public static function respond(ResponseInterface $response, array $data, int $status = 200): ResponseInterface
    {
        try {
            $json = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        } catch (JsonException) {
            $json = '{"type":"about:blank","title":"Serialization error","status":500}';
            $status = 500;
        }
        $response->getBody()->write($json);

        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}
