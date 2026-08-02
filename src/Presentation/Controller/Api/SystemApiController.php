<?php

declare(strict_types=1);

namespace BackupCatalog\Presentation\Controller\Api;

use BackupCatalog\Application\Service\SystemService;
use BackupCatalog\Infrastructure\Repository\SystemRepository;
use BackupCatalog\Presentation\Http\JsonResponder;
use BackupCatalog\Presentation\Http\RequestData;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class SystemApiController
{
    public function __construct(
        private SystemRepository $systems,
        private SystemService $service,
    ) {}

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $query = $request->getQueryParams();
        $filters = [
            'criticality' => (string) ($query['criticality'] ?? ''),
            'health' => (string) ($query['health'] ?? ''),
            'query' => (string) ($query['query'] ?? ''),
        ];

        return JsonResponder::respond(
            $response,
            $this->systems->paginate($filters, RequestData::page($request), RequestData::size($request)),
        );
    }

    /** @param array{id: string} $arguments */
    public function show(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $arguments,
    ): ResponseInterface {
        return JsonResponder::respond($response, ['data' => $this->systems->find((int) $arguments['id'])]);
    }

    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $system = $this->service->create(RequestData::body($request));

        return JsonResponder::respond($response, ['data' => $system], 201)
            ->withHeader('Location', '/api/systems/' . $system['id']);
    }

    /** @param array{id: string} $arguments */
    public function update(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $arguments,
    ): ResponseInterface {
        $system = $this->service->update((int) $arguments['id'], RequestData::body($request));

        return JsonResponder::respond($response, ['data' => $system]);
    }

    /** @param array{id: string} $arguments */
    public function delete(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $arguments,
    ): ResponseInterface {
        $this->systems->delete((int) $arguments['id']);

        return $response->withStatus(204);
    }
}
