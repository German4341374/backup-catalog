<?php

declare(strict_types=1);

namespace BackupCatalog\Presentation\Controller;

use BackupCatalog\Presentation\Http\JsonResponder;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final readonly class HealthController
{
    public function __construct(private PDO $pdo) {}

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $statement = $this->pdo->prepare('SELECT 1');
            $statement->execute();
            $statement->fetchColumn();

            return JsonResponder::respond($response, [
                'status' => 'ok',
                'checks' => ['database' => 'up'],
            ]);
        } catch (Throwable) {
            return JsonResponder::respond($response, [
                'status' => 'degraded',
                'checks' => ['database' => 'down'],
            ], 503);
        }
    }
}
