<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use LorneQuinn\Blog\Core\Enums\PostStatus;
use LorneQuinn\Blog\Core\Models\Post;
use LorneQuinn\Blog\Mcp\BlogServer;
use LorneQuinn\Blog\Mcp\Tools\ListPostsTool;

uses(RefreshDatabase::class);

it('returns the most recently-updated posts first', function (): void {
    $older = Post::factory()->create(['title' => 'Older']);
    $older->update(['updated_at' => now()->subDay()]);
    $newer = Post::factory()->create(['title' => 'Newer']);

    BlogServer::tool(ListPostsTool::class, [])
        ->assertOk()
        ->assertStructuredContent(structuredJson(fn ($json) => $json
            ->where('posts.0.title', 'Newer')
            ->where('posts.1.title', 'Older')
            ->etc()
        ));
});

it('filters by status', function (): void {
    Post::factory()->create(['title' => 'Draft 1', 'status' => PostStatus::Draft]);
    Post::factory()->create(['title' => 'Published 1', 'status' => PostStatus::Published, 'published_at' => now()]);

    BlogServer::tool(ListPostsTool::class, ['status' => 'published'])
        ->assertOk()
        ->assertStructuredContent(structuredJson(fn ($json) => $json
            ->where('total', 1)
            ->where('posts.0.title', 'Published 1')
            ->etc()
        ));
});

it('rejects invalid status', function (): void {
    BlogServer::tool(ListPostsTool::class, ['status' => 'archived'])
        ->assertHasErrors();
});

it('paginates with per_page and page', function (): void {
    Post::factory()->count(5)->create();

    BlogServer::tool(ListPostsTool::class, ['per_page' => 2, 'page' => 2])
        ->assertOk()
        ->assertStructuredContent(structuredJson(fn ($json) => $json
            ->where('page', 2)
            ->where('per_page', 2)
            ->where('total', 5)
            ->where('last_page', 3)
            ->has('posts', 2)
            ->etc()
        ));
});
