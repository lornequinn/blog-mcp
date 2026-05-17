<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use LorneQuinn\Blog\Core\Models\Post;
use LorneQuinn\Blog\Mcp\BlogServer;
use LorneQuinn\Blog\Mcp\Tools\DeletePostTool;

uses(RefreshDatabase::class);

it('hard-deletes a Post by slug', function (): void {
    $post = Post::factory()->create(['title' => 'To Delete']);
    $slug = $post->slug;
    $id = $post->id;

    BlogServer::tool(DeletePostTool::class, ['slug' => $slug])
        ->assertOk()
        ->assertStructuredContent([
            'deleted' => true,
            'id' => $id,
            'slug' => $slug,
        ]);

    expect(Post::query()->find($id))->toBeNull();
});

it('errors when post not found', function (): void {
    BlogServer::tool(DeletePostTool::class, ['slug' => 'missing'])
        ->assertHasErrors(['not found']);
});
