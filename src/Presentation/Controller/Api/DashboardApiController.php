<?php

declare(strict_types=1);

namespace BackupCatalog\Presentation\Controller\Api;

use BackupCatalog\Application\Service\DashboardService;
use BackupCatalog\Presentation\Http\JsonResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class DashboardApiController
{
    public function __construct(private DashboardService $dashboard) {}

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return JsonResponder::respond($response, ['data' => $this->dashboard->overview()]);
    }
}
