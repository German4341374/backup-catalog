<?php

declare(strict_types=1);

namespace BackupCatalog\Presentation\Controller\Web;

use BackupCatalog\Application\Exception\ValidationException;
use BackupCatalog\Application\Service\BackupImportService;
use BackupCatalog\Application\Service\BackupRunService;
use BackupCatalog\Application\Service\CsvExportService;
use BackupCatalog\Infrastructure\Repository\BackupJobRepository;
use BackupCatalog\Infrastructure\Repository\BackupRunRepository;
use BackupCatalog\Infrastructure\Repository\SystemRepository;
use BackupCatalog\Presentation\Http\Flash;
use BackupCatalog\Presentation\Http\RequestData;
use BackupCatalog\Presentation\View\Renderer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

final readonly class BackupRunController
{
    public function __construct(
        private BackupRunRepository $runs,
        private BackupRunService $service,
        private BackupImportService $importer,
        private CsvExportService $csv,
        private SystemRepository $systems,
        private BackupJobRepository $jobs,
        private Renderer $renderer,
        private Flash $flash,
    ) {}

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $filters = $this->filters($request);

        return $this->renderer->render($response, 'runs/index.html.twig', [
            'result' => $this->runs->paginate($filters, RequestData::page($request), 25),
            'systems' => $this->systems->all(),
            'filters' => $filters,
            'flash' => $this->flash->consume(),
        ]);
    }

    public function createForm(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->renderer->render($response, 'runs/form.html.twig', [
            'systems' => $this->systems->all(),
            'jobs' => $this->jobs->allEnabled(),
        ]);
    }

    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->service->register(RequestData::body($request));
        $this->flash->set('success', 'Backup run registered successfully.');

        return $response->withHeader('Location', '/backup-runs')->withStatus(303);
    }

    public function importForm(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->renderer->render($response, 'runs/import.html.twig');
    }

    public function import(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $uploaded = $request->getUploadedFiles()['backup_file'] ?? null;
        if (!$uploaded instanceof UploadedFileInterface || UPLOAD_ERR_OK !== $uploaded->getError()) {
            throw new ValidationException(['backup_file' => 'Choose a valid JSON file.']);
        }
        if ($uploaded->getSize() > 5 * 1024 * 1024) {
            throw new ValidationException(['backup_file' => 'Import files must not exceed 5 MB.']);
        }
        $result = $this->importer->importJson((string) $uploaded->getStream());
        $this->flash->set('success', sprintf(
            'Imported %d runs; skipped %d existing records.',
            $result['imported'],
            $result['skipped'],
        ));

        return $response->withHeader('Location', '/backup-runs')->withStatus(303);
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
            'systemId' => (int) ($query['systemId'] ?? 0),
            'status' => (string) ($query['status'] ?? ''),
            'type' => (string) ($query['type'] ?? ''),
            'from' => (string) ($query['from'] ?? ''),
            'to' => (string) ($query['to'] ?? ''),
        ];
    }
}
