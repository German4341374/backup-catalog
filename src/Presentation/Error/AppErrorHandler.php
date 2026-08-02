<?php

declare(strict_types=1);

namespace BackupCatalog\Presentation\Error;

use BackupCatalog\Application\Exception\NotFoundException;
use BackupCatalog\Application\Exception\ValidationException;
use BackupCatalog\Presentation\Http\JsonResponder;
use BackupCatalog\Presentation\View\Renderer;
use PDOException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpNotFoundException;
use Throwable;

final readonly class AppErrorHandler
{
    public function __construct(
        private ResponseFactoryInterface $responses,
        private Renderer $renderer,
        private bool $displayErrors,
    ) {}

    public function __invoke(
        ServerRequestInterface $request,
        Throwable $exception,
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails,
    ): ResponseInterface {
        [$status, $title] = $this->statusAndTitle($exception);
        $requestId = (string) $request->getAttribute('request_id', 'unavailable');
        $log = json_encode([
            'level' => 'error',
            'event' => 'request_failed',
            'request_id' => $requestId,
            'status' => $status,
            'exception' => $exception::class,
        ], JSON_UNESCAPED_SLASHES);
        error_log(false === $log ? '{"level":"error","event":"request_failed"}' : $log);

        $detail = $status < 500 || $this->displayErrors
            ? $exception->getMessage()
            : 'An unexpected error occurred.';
        $errors = $exception instanceof ValidationException ? $exception->errors() : null;
        $response = $this->responses->createResponse($status);

        if (str_starts_with($request->getUri()->getPath(), '/api/')) {
            return JsonResponder::respond($response, [
                'error' => [
                    'status' => $status,
                    'title' => $title,
                    'detail' => $detail,
                    'requestId' => $requestId,
                    'errors' => $errors,
                ],
            ], $status);
        }

        return $this->renderer->render($response, 'error.html.twig', [
            'status' => $status,
            'title' => $title,
            'detail' => $detail,
            'requestId' => $requestId,
            'errors' => $errors,
        ]);
    }

    /** @return array{int, string} */
    private function statusAndTitle(Throwable $exception): array
    {
        return match (true) {
            $exception instanceof ValidationException => [422, 'Validation failed'],
            $exception instanceof NotFoundException, $exception instanceof HttpNotFoundException => [404, 'Not found'],
            $exception instanceof PDOException && in_array($exception->getCode(), ['23000', '23503', '23505'], true) => [409, 'Database conflict'],
            default => [500, 'Internal server error'],
        };
    }
}
