<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use LorneQuinn\Blog\Core\Component\Component;
use LorneQuinn\Blog\Core\Component\ComponentRegistry;
use LorneQuinn\Blog\Core\Models\Post;
use LorneQuinn\Blog\Mcp\BlogServer;
use LorneQuinn\Blog\Mcp\Tools\ValidatePostTool;

uses(RefreshDatabase::class);

function registerDemoComponent(): void
{
    $stub = new class extends Component
    {
        public function name(): string
        {
            return 'demo';
        }

        public function dataType(): string
        {
            return 'demo-data';
        }

        public function view(): string
        {
            return 'demo';
        }

        public function requiredAttributes(): array
        {
            return ['label'];
        }

        public function optionalAttributes(): array
        {
            return ['size' => 'md'];
        }
    };

    app(ComponentRegistry::class)->register($stub);
}

it('passes a body with no shortcodes', function (): void {
    BlogServer::tool(ValidatePostTool::class, ['body' => 'Just text with **bold**.'])
        ->assertOk()
        ->assertStructuredContent(structuredJson(fn ($json) => $json
            ->where('ok', true)
            ->where('shortcode_count', 0)
            ->etc()
        ));
});

it('flags unknown shortcode names', function (): void {
    BlogServer::tool(ValidatePostTool::class, ['body' => 'Text [[mystery]] more.'])
        ->assertOk()
        ->assertStructuredContent(structuredJson(fn ($json) => $json
            ->where('ok', false)
            ->where('shortcode_count', 1)
            ->where('issues.0.shortcode', 'mystery')
            ->where('issues.0.issue', 'unknown-component')
            ->etc()
        ));
});

it('flags missing required attributes', function (): void {
    registerDemoComponent();

    BlogServer::tool(ValidatePostTool::class, ['body' => '[[demo]]'])
        ->assertOk()
        ->assertStructuredContent(structuredJson(fn ($json) => $json
            ->where('ok', false)
            ->where('issues.0.issue', 'missing-required-attr')
            ->etc()
        ));
});

it('flags unknown attributes', function (): void {
    registerDemoComponent();

    BlogServer::tool(ValidatePostTool::class, ['body' => '[[demo label="ok" zzz="no"]]'])
        ->assertOk()
        ->assertStructuredContent(structuredJson(fn ($json) => $json
            ->where('ok', false)
            ->where('issues.0.issue', 'unknown-attr')
            ->etc()
        ));
});

it('accepts a valid shortcode with required and optional attrs', function (): void {
    registerDemoComponent();

    BlogServer::tool(ValidatePostTool::class, ['body' => '[[demo label="Hi" size="lg"]]'])
        ->assertOk()
        ->assertStructuredContent(structuredJson(fn ($json) => $json
            ->where('ok', true)
            ->where('shortcode_count', 1)
            ->etc()
        ));
});

it('validates an existing Post by slug', function (): void {
    $post = Post::factory()->create(['body' => 'No shortcodes here.']);

    BlogServer::tool(ValidatePostTool::class, ['slug' => $post->slug])
        ->assertOk()
        ->assertStructuredContent(structuredJson(fn ($json) => $json
            ->where('ok', true)
            ->etc()
        ));
});
