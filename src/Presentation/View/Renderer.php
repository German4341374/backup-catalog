<?php

declare(strict_types=1);

namespace BackupCatalog\Presentation\View;

use Psr\Http\Message\ResponseInterface;
use Twig\Environment;

final readonly class Renderer
{
    public function __construct(private Environment $twig) {}

    /** @param array<string, mixed> $data */
    public function render(ResponseInterface $response, string $template, array $data = []): ResponseInterface
    {
        $response->getBody()->write($this->twig->render($template, $data));

        return $response->withHeader('Content-Type', 'text/html; charset=utf-8');
    }
}
