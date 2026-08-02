<?php

declare(strict_types=1);

namespace BackupCatalog\Presentation\Controller\Web;

use BackupCatalog\Application\Service\SystemService;
use BackupCatalog\Infrastructure\Repository\BackupJobRepository;
use BackupCatalog\Infrastructure\Repository\BackupRunRepository;
use BackupCatalog\Infrastructure\Repository\SystemRepository;
use BackupCatalog\Presentation\Http\Flash;
use BackupCatalog\Presentation\Http\RequestData;
use BackupCatalog\Presentation\View\Renderer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class SystemController
{
    public function __construct(
        private SystemRepository $systems,
        private BackupJobRepository $jobs,
        private BackupRunRepository $runs,
        private SystemService $service,
        private Renderer $renderer,
        private Flash $flash,
    ) {}

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $query = $request->getQueryParams();
        $filters = [
            'query' => (string) ($query['query'] ?? ''),
            'criticality' => (string) ($query['criticality'] ?? ''),
            'health' => (string) ($query['health'] ?? ''),
        ];

        return $this->renderer->render($response, 'systems/index.html.twig', [
            'result' => $this->systems->paginate($filters, RequestData::page($request), 20),
            'filters' => $filters,
            'flash' => $this->flash->consume(),
        ]);
    }

    public function createForm(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->renderer->render($response, 'systems/form.html.twig', ['system' => null]);
    }

    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $system = $this->service->create(RequestData::body($request));
        $this->flash->set('success', 'System created successfully.');

        return $response->withHeader('Location', '/systems/' . $system['id'])->withStatus(303);
    }

    /** @param array{id: string} $arguments */
    public function show(ServerRequestInterface $request, ResponseInterface $response, array $arguments): ResponseInterface
    {
        $id = (int) $arguments['id'];

        return $this->renderer->render($response, 'systems/show.html.twig', [
            'system' => $this->systems->find($id),
            'jobs' => $this->jobs->forSystem($id),
            'runs' => $this->runs->paginate(['systemId' => $id], 0, 10)['items'],
            'flash' => $this->flash->consume(),
        ]);
    }

    /** @param array{id: string} $arguments */
    public function editForm(ServerRequestInterface $request, ResponseInterface $response, array $arguments): ResponseInterface
    {
        return $this->renderer->render($response, 'systems/form.html.twig', [
            'system' => $this->systems->find((int) $arguments['id']),
        ]);
    }

    /** @param array{id: string} $arguments */
    public function update(ServerRequestInterface $request, ResponseInterface $response, array $arguments): ResponseInterface
    {
        $id = (int) $arguments['id'];
        $this->service->update($id, RequestData::body($request));
        $this->flash->set('success', 'System updated successfully.');

        return $response->withHeader('Location', '/systems/' . $id)->withStatus(303);
    }

    /** @param array{id: string} $arguments */
    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $arguments): ResponseInterface
    {
        $this->systems->delete((int) $arguments['id']);
        $this->flash->set('success', 'System deleted successfully.');

        return $response->withHeader('Location', '/systems')->withStatus(303);
    }
}
