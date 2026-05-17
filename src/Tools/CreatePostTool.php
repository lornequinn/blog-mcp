<?php

declare(strict_types=1);

namespace LorneQuinn\Blog\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use LorneQuinn\Blog\Core\Enums\PostStatus;
use LorneQuinn\Blog\Core\Models\Post;

#[Description('Create a new Post. Slug auto-generates from title if omitted.')]
class CreatePostTool extends Tool
{
    public function handle(Request $request): ResponseFactory
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9-]+$/'],
            'status' => ['nullable', 'string', 'in:draft,scheduled,published'],
            'published_at' => ['nullable', 'date'],
        ]);

        $status = isset($validated['status'])
            ? PostStatus::from($validated['status'])
            : PostStatus::Draft;

        $post = new Post;
        $post->title = $validated['title'];
        $post->body = $validated['body'] ?? null;
        $post->excerpt = $validated['excerpt'] ?? null;
        if (isset($validated['slug'])) {
            $post->slug = $validated['slug'];
        }
        $post->status = $status;
        $post->published_at = $status === PostStatus::Published
            ? ($validated['published_at'] ?? now())
            : ($validated['published_at'] ?? null);
        $post->save();

        $post->load('dataLinks');

        return Response::structured(GetPostTool::serialize($post));
    }

    /** @return array<string, mixed> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->required()->description('Post title.'),
            'body' => $schema->string()->description('Markdown body.'),
            'excerpt' => $schema->string()->description('Short summary.'),
            'slug' => $schema->string()->description('Override slug (lowercase, hyphens). Auto-generated from title if omitted.'),
            'status' => $schema->string()
                ->enum(['draft', 'scheduled', 'published'])
                ->description('Default: draft.'),
            'published_at' => $schema->string()->description('ISO datetime. Required for scheduled; defaults to now() for published.'),
        ];
    }
}
