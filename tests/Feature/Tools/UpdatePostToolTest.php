<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use LorneQuinn\Blog\Core\Models\Post;
use LorneQuinn\Blog\Mcp\BlogServer;
use LorneQuinn\Blog\Mcp\Tools\UpdatePostTool;

uses(RefreshDatabase::class);

it('updates the title and body but leaves other fields alone', function (): void {
    $post = Post::factory()->create([
        'title' => 'Old Title',
        'body' => 'Old body',
        'excerpt' => 'Keep me',
    ]);

    BlogServer::tool(UpdatePostTool::class, [
        'slug' => $post->slug,
        'title' => 'New Title',
        'body' => 'New body',
    ])
        ->assertOk()
        ->assertStructuredContent(structuredJson(fn ($json) => $json
            ->where('title', 'New Title')
            ->where('body', 'New body')
            ->where('excerpt', 'Keep me')
            ->etc()
        ));
});

it('renames the slug via new_slug', function (): void {
    $post = Post::factory()->create(['title' => 'Original']);

    BlogServer::tool(UpdatePostTool::class, [
        'slug' => $post->slug,
        'new_slug' => 'renamed-post',
    ])
        ->assertOk()
        ->assertStructuredContent(structuredJson(fn ($json) => $json
            ->where('slug', 'renamed-post')
            ->etc()
        ));

    expect(Post::query()->where('slug', 'renamed-post')->exists())->toBeTrue();
});

it('errors when neither identifier is provided', function (): void {
    BlogServer::tool(UpdatePostTool::class, ['title' => 'No ID'])
        ->assertHasErrors(['Provide either']);
});
