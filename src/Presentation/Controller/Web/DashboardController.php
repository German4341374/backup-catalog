<?php

declare(strict_types=1);

namespace BackupCatalog\Presentation\Controller\Web;

use BackupCatalog\Application\Service\DashboardService;
use BackupCatalog\Presentation\Http\Flash;
use BackupCatalog\Presentation\View\Renderer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class DashboardController
{
    public function __construct(
        private DashboardService $dashboard,
        private Renderer $renderer,
        private Flash $flash,
    ) {}

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->renderer->render($response, 'dashboard.html.twig', [
            ...$this->dashboard->overview(),
            'flash' => $this->flash->consume(),
        ]);
    }
}
