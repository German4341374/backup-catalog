<?php

declare(strict_types=1);

use BackupCatalog\Presentation\Error\AppErrorHandler;
use BackupCatalog\Presentation\Middleware\CsrfMiddleware;
use BackupCatalog\Presentation\Middleware\RequestIdMiddleware;
use Dotenv\Dotenv;
use Slim\App;
use Slim\Factory\AppFactory;

require dirname(__DIR__) . '/vendor/autoload.php';

date_default_timezone_set('UTC');
Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
$container = require dirname(__DIR__) . '/config/container.php';
AppFactory::setContainer($container);
$app = AppFactory::create();

/** @var callable(App<\Psr\Container\ContainerInterface|null>): void $routes */
$routes = require dirname(__DIR__) . '/config/routes.php';
$routes($app);

$app->add($container->get(CsrfMiddleware::class));
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$errorMiddleware = $app->addErrorMiddleware(false, true, true);
$errorMiddleware->setDefaultErrorHandler($container->get(AppErrorHandler::class));
$app->add(new RequestIdMiddleware());

return $app;
