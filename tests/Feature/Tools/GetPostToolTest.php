<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use LorneQuinn\Blog\Core\Models\Post;
use LorneQuinn\Blog\Mcp\BlogServer;
use LorneQuinn\Blog\Mcp\Tools\GetPostTool;

uses(RefreshDatabase::class);

it('fetches a Post by slug', function (): void {
    $post = Post::factory()->create(['title' => 'Hello World', 'body' => 'body text']);

    BlogServer::tool(GetPostTool::class, ['slug' => $post->slug])
        ->assertOk()
        ->assertStructuredContent(structuredJson(fn ($json) => $json
            ->where('id', $post->id)
            ->where('slug', $post->slug)
            ->where('title', 'Hello World')
            ->where('body', 'body text')
            ->etc()
        ));
});

it('fetches a Post by id', function (): void {
    $post = Post::factory()->create(['title' => 'By ID']);

    BlogServer::tool(GetPostTool::class, ['id' => $post->id])
        ->assertOk()
        ->assertStructuredContent(structuredJson(fn ($json) => $json
            ->where('id', $post->id)
            ->where('title', 'By ID')
            ->etc()
        ));
});

it('errors when neither slug nor id is provided', function (): void {
    BlogServer::tool(GetPostTool::class, [])
        ->assertHasErrors(['Provide either']);
});

it('errors when slug does not exist', function (): void {
    BlogServer::tool(GetPostTool::class, ['slug' => 'nope'])
        ->assertHasErrors(['Post not found']);
});
