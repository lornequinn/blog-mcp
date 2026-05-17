<?php

declare(strict_types=1);

namespace LorneQuinn\Blog\Mcp\Tests;

use Illuminate\Foundation\Application;
use Laravel\Mcp\Server\McpServiceProvider;
use LorneQuinn\Blog\Core\BlogCoreServiceProvider;
use LorneQuinn\Blog\Mcp\BlogMcpServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            McpServiceProvider::class,
            BlogCoreServiceProvider::class,
            BlogMcpServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        /** @var Application $app */
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}
