<?php

declare(strict_types=1);

use BackupCatalog\Presentation\Controller\Api\BackupRunApiController;
use BackupCatalog\Presentation\Controller\Api\DashboardApiController;
use BackupCatalog\Presentation\Controller\Api\SystemApiController;
use BackupCatalog\Presentation\Controller\HealthController;
use BackupCatalog\Presentation\Controller\Web\BackupRunController;
use BackupCatalog\Presentation\Controller\Web\DashboardController;
use BackupCatalog\Presentation\Controller\Web\SystemController;
use Slim\App;

return static function (App $app): void {
    $app->get('/health', HealthController::class);

    $app->get('/', DashboardController::class);
    $app->get('/systems', [SystemController::class, 'index']);
    $app->get('/systems/new', [SystemController::class, 'createForm']);
    $app->post('/systems', [SystemController::class, 'create']);
    $app->get('/systems/{id:[0-9]+}', [SystemController::class, 'show']);
    $app->get('/systems/{id:[0-9]+}/edit', [SystemController::class, 'editForm']);
    $app->post('/systems/{id:[0-9]+}', [SystemController::class, 'update']);
    $app->post('/systems/{id:[0-9]+}/delete', [SystemController::class, 'delete']);

    $app->get('/backup-runs', [BackupRunController::class, 'index']);
    $app->get('/backup-runs/new', [BackupRunController::class, 'createForm']);
    $app->post('/backup-runs', [BackupRunController::class, 'create']);
    $app->get('/backup-runs/import', [BackupRunController::class, 'importForm']);
    $app->post('/backup-runs/import', [BackupRunController::class, 'import']);
    $app->get('/backup-runs/export.csv', [BackupRunController::class, 'export']);

    $app->group('/api', function ($group): void {
        $group->get('/dashboard', DashboardApiController::class);
        $group->get('/systems', [SystemApiController::class, 'index']);
        $group->post('/systems', [SystemApiController::class, 'create']);
        $group->get('/systems/{id:[0-9]+}', [SystemApiController::class, 'show']);
        $group->put('/systems/{id:[0-9]+}', [SystemApiController::class, 'update']);
        $group->patch('/systems/{id:[0-9]+}', [SystemApiController::class, 'update']);
        $group->delete('/systems/{id:[0-9]+}', [SystemApiController::class, 'delete']);
        $group->get('/backup-runs', [BackupRunApiController::class, 'index']);
        $group->post('/backup-runs', [BackupRunApiController::class, 'create']);
        $group->post('/backup-runs/import', [BackupRunApiController::class, 'import']);
        $group->get('/backup-runs/export.csv', [BackupRunApiController::class, 'export']);
        $group->get('/backup-runs/{id:[0-9]+}', [BackupRunApiController::class, 'show']);
    });
};
