<?php

declare(strict_types=1);

namespace BackupCatalog\Tests\Integration;

use BackupCatalog\Infrastructure\Database\Migrator;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Slim\App;
use Slim\Psr7\Factory\ServerRequestFactory;
use Throwable;

final class HttpApiTest extends TestCase
{
    /** @var App<ContainerInterface|null> */
    private App $app;

    protected function setUp(): void
    {
        putenv('DATABASE_DSN=' . (getenv('TEST_DATABASE_DSN') ?: 'pgsql:host=127.0.0.1;port=5432;dbname=backup_catalog_test'));
        putenv('DATABASE_USER=' . (getenv('TEST_DATABASE_USER') ?: 'backup_catalog'));
        putenv('DATABASE_PASSWORD=' . (getenv('TEST_DATABASE_PASSWORD') ?: 'backup_catalog_test'));
        putenv('APP_DEBUG=false');
        if (PHP_SESSION_ACTIVE !== session_status()) {
            session_start();
        }
        try {
            $this->app = require dirname(__DIR__, 2) . '/bootstrap/app.php';
            $container = $this->app->getContainer();
            if (!$container instanceof ContainerInterface) {
                throw new \RuntimeException('Application container is unavailable.');
            }
            $container->get(Migrator::class)->migrate();
        } catch (Throwable $exception) {
            self::markTestSkipped('PostgreSQL integration database is unavailable: ' . $exception->getMessage());
        }
    }

    public function testHealthEndpointReportsDatabase(): void
    {
        $response = $this->app->handle(new ServerRequestFactory()->createServerRequest('GET', '/health'));

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('"database":"up"', (string) $response->getBody());
    }

    public function testSystemsEndpointReturnsPagination(): void
    {
        $response = $this->app->handle(new ServerRequestFactory()->createServerRequest('GET', '/api/systems'));
        $json = json_decode((string) $response->getBody(), true, 32, JSON_THROW_ON_ERROR);

        self::assertSame(200, $response->getStatusCode());
        self::assertGreaterThanOrEqual(8, $json['total']);
        self::assertNotEmpty($json['items']);
    }

    public function testUnknownApiResourceUsesUniformErrorShape(): void
    {
        $response = $this->app->handle(new ServerRequestFactory()->createServerRequest('GET', '/api/systems/999999'));
        $json = json_decode((string) $response->getBody(), true, 32, JSON_THROW_ON_ERROR);

        self::assertSame(404, $response->getStatusCode());
        self::assertSame('Not found', $json['error']['title']);
        self::assertArrayHasKey('requestId', $json['error']);
    }
}
