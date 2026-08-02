<?php

declare(strict_types=1);

use BackupCatalog\Infrastructure\Database\Migrator;
use Slim\App;

session_set_cookie_params([
    'httponly' => true,
    'secure' => filter_var($_SERVER['HTTPS'] ?? false, FILTER_VALIDATE_BOOL),
    'samesite' => 'Lax',
]);
session_start();

/** @var App<\Psr\Container\ContainerInterface> $app */
$app = require dirname(__DIR__) . '/bootstrap/app.php';
if (filter_var($_ENV['MIGRATE_ON_START'] ?? $_SERVER['MIGRATE_ON_START'] ?? getenv('MIGRATE_ON_START') ?: true, FILTER_VALIDATE_BOOL)) {
    $app->getContainer()?->get(Migrator::class)->migrate();
}
$app->run();
