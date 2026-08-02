<?php

declare(strict_types=1);

use BackupCatalog\Application\Service\DashboardService;
use BackupCatalog\Infrastructure\Database\ConnectionFactory;
use BackupCatalog\Infrastructure\Database\Migrator;
use BackupCatalog\Presentation\Error\AppErrorHandler;
use BackupCatalog\Presentation\Security\CsrfToken;
use BackupCatalog\Presentation\View\ViewFormatter;

use function DI\autowire;

use DI\ContainerBuilder;

use function DI\factory;
use function DI\get;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;

$env = static fn(string $name, mixed $default = null): mixed =>
    $_ENV[$name] ?? $_SERVER[$name] ?? getenv($name) ?: $default;

$builder = new ContainerBuilder();
$builder->useAutowiring(true);
$builder->addDefinitions([
    'root' => dirname(__DIR__),
    'display_errors' => filter_var($env('APP_DEBUG', false), FILTER_VALIDATE_BOOL),
    PDO::class => factory(static fn(): PDO => ConnectionFactory::create(
        (string) $env('DATABASE_DSN', 'pgsql:host=127.0.0.1;port=5432;dbname=backup_catalog'),
        (string) $env('DATABASE_USER', 'backup_catalog'),
        (string) $env('DATABASE_PASSWORD', ''),
    )),
    ResponseFactoryInterface::class => autowire(ResponseFactory::class),
    Migrator::class => autowire()->constructorParameter(
        'migrationDirectory',
        dirname(__DIR__) . '/database/migrations',
    ),
    DashboardService::class => autowire()->constructorParameter(
        'overdueRunningHours',
        max(1, (int) $env('OVERDUE_RUNNING_HOURS', 12)),
    ),
    CsrfToken::class => autowire(),
    Environment::class => factory(static function (ContainerInterface $container): Environment {
        $twig = new Environment(new FilesystemLoader($container->get('root') . '/templates'), [
            'cache' => false,
            'autoescape' => 'html',
            'strict_variables' => true,
        ]);
        $csrf = $container->get(CsrfToken::class);
        $twig->addFunction(new TwigFunction('csrf_token', static fn(): string => $csrf->value()));
        $twig->addFilter(new TwigFilter('bytes', ViewFormatter::bytes(...)));
        $twig->addFilter(new TwigFilter('utc_date', ViewFormatter::date(...)));
        $twig->addFilter(new TwigFilter('relative_time', ViewFormatter::relative(...)));
        $twig->addFunction(new TwigFunction('duration', ViewFormatter::duration(...)));

        return $twig;
    }),
    AppErrorHandler::class => autowire()->constructorParameter('displayErrors', get('display_errors')),
]);

return $builder->build();
