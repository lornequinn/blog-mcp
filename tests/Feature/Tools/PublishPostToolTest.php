<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use LorneQuinn\Blog\Core\Enums\PostStatus;
use LorneQuinn\Blog\Core\Models\Post;
use LorneQuinn\Blog\Mcp\BlogServer;
use LorneQuinn\Blog\Mcp\Tools\PublishPostTool;
use LorneQuinn\Blog\Mcp\Tools\UnpublishPostTool;

uses(RefreshDatabase::class);

it('publishes a draft Post and stamps published_at', function (): void {
    $post = Post::factory()->create(['status' => PostStatus::Draft, 'published_at' => null]);

    BlogServer::tool(PublishPostTool::class, ['slug' => $post->slug])
        ->assertOk()
        ->assertStructuredContent(structuredJson(fn ($json) => $json
            ->where('status', 'published')
            ->whereNot('published_at', null)
            ->etc()
        ));

    expect($post->fresh()->status)->toBe(PostStatus::Published);
});

it('unpublishes back to draft and clears published_at', function (): void {
    $post = Post::factory()->create(['status' => PostStatus::Published, 'published_at' => now()]);

    BlogServer::tool(UnpublishPostTool::class, ['slug' => $post->slug])
        ->assertOk()
        ->assertStructuredContent(structuredJson(fn ($json) => $json
            ->where('status', 'draft')
            ->where('published_at', null)
            ->etc()
        ));

    $fresh = $post->fresh();
    expect($fresh)->not->toBeNull();
    expect($fresh->status)->toBe(PostStatus::Draft);
    expect($fresh->published_at)->toBeNull();
});
