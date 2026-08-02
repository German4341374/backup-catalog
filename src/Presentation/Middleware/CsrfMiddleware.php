<?php

declare(strict_types=1);

namespace BackupCatalog\Presentation\Middleware;

use BackupCatalog\Application\Exception\ValidationException;
use BackupCatalog\Presentation\Security\CsrfToken;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class CsrfMiddleware implements MiddlewareInterface
{
    public function __construct(private CsrfToken $token) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $method = strtoupper($request->getMethod());
        $path = $request->getUri()->getPath();
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true) || str_starts_with($path, '/api/')) {
            return $handler->handle($request);
        }
        $body = $request->getParsedBody();
        $candidate = is_array($body) ? (string) ($body['_csrf'] ?? '') : '';
        if (!$this->token->isValid($candidate)) {
            throw new ValidationException(['_csrf' => 'The form expired. Refresh the page and try again.']);
        }

        return $handler->handle($request);
    }
}
