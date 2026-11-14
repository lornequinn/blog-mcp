<?php

declare(strict_types=1);

namespace LorneQuinn\Blog\Mcp\Tests\Fixtures;

use Illuminate\Support\ServiceProvider;

/**
 * Registers the `blog-mcp-test::` view namespace used by test Components.
 * Loaded by the test suite and by testbench.yaml, so static analysis sees the same views the tests render.
 */
final class TestViewsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/views', 'blog-mcp-test');
    }
}
