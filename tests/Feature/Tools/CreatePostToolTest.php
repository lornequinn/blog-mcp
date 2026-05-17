<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use LorneQuinn\Blog\Core\Models\Post;
use LorneQuinn\Blog\Mcp\BlogServer;
use LorneQuinn\Blog\Mcp\Tools\CreatePostTool;

uses(RefreshDatabase::class);

it('creates a draft Post with auto slug', function (): void {
    BlogServer::tool(CreatePostTool::class, [
        'title' => 'Hello World',
        'body' => '# Heading',
    ])
        ->assertOk()
        ->assertStructuredContent(structuredJson(fn ($json) => $json
            ->where('slug', 'hello-world')
            ->where('status', 'draft')
            ->where('body', '# Heading')
            ->etc()
        ));

    expect(Post::query()->where('slug', 'hello-world')->exists())->toBeTrue();
});

it('creates a published Post and stamps published_at', function (): void {
    BlogServer::tool(CreatePostTool::class, [
        'title' => 'Live Now',
        'status' => 'published',
    ])
        ->assertOk()
        ->assertStructuredContent(structuredJson(fn ($json) => $json
            ->where('status', 'published')
            ->whereNot('published_at', null)
            ->etc()
        ));
});

it('rejects missing required title', function (): void {
    BlogServer::tool(CreatePostTool::class, ['body' => 'no title'])
        ->assertHasErrors();
});

it('rejects an invalid slug format', function (): void {
    BlogServer::tool(CreatePostTool::class, [
        'title' => 'Bad Slug',
        'slug' => 'Bad Slug With Spaces',
    ])
        ->assertHasErrors();
});
