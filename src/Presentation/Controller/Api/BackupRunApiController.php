<?php

declare(strict_types=1);

namespace BackupCatalog\Presentation\Controller\Api;

use BackupCatalog\Application\Exception\ValidationException;
use BackupCatalog\Application\Service\BackupImportService;
use BackupCatalog\Application\Service\BackupRunService;
use BackupCatalog\Application\Service\CsvExportService;
use BackupCatalog\Infrastructure\Repository\BackupRunRepository;
use BackupCatalog\Presentation\Http\JsonResponder;
use BackupCatalog\Presentation\Http\RequestData;
use JsonException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class BackupRunApiController
{
    public function __construct(
        private BackupRunRepository $runs,
        private BackupRunService $service,
        private BackupImportService $importer,
        private CsvExportService $csv,
    ) {}

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return JsonResponder::respond(
            $response,
            $this->runs->paginate(
                $this->filters($request),
                RequestData::page($request),
                RequestData::size($request),
            ),
        );
    }

    /** @param array{id: string} $arguments */
    public function show(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $arguments,
    ): ResponseInterface {
        return JsonResponder::respond($response, ['data' => $this->runs->find((int) $arguments['id'])]);
    }

    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $run = $this->service->register(RequestData::body($request));

        return JsonResponder::respond($response, ['data' => $run], 201)
            ->withHeader('Location', '/api/backup-runs/' . $run['id']);
    }

    public function import(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $json = json_encode(RequestData::body($request), JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new ValidationException(['body' => 'Unable to process the import document.']);
        }

        return JsonResponder::respond($response, ['data' => $this->importer->importJson($json)], 201);
    }

    public function export(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $response->getBody()->write($this->csv->export($this->filters($request)));

        return $response
            ->withHeader('Content-Type', 'text/csv; charset=utf-8')
            ->withHeader('Content-Disposition', 'attachment; filename="backup-runs.csv"');
    }

    /** @return array{systemId?: int, status?: string, type?: string, from?: string, to?: string} */
    private function filters(ServerRequestInterface $request): array
    {
        $query = $request->getQueryParams();

        return [
            'systemId' => (int) ($query['systemId'] ?? $query['system_id'] ?? 0),
            'status' => (string) ($query['status'] ?? ''),
            'type' => (string) ($query['type'] ?? ''),
            'from' => (string) ($query['from'] ?? ''),
            'to' => (string) ($query['to'] ?? ''),
        ];
    }
}
