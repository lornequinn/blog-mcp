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

#[Description('Partial update of a Post. Identify by slug or id; only fields provided are touched.')]
class UpdatePostTool extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'slug' => ['nullable', 'string'],
            'id' => ['nullable', 'integer'],
            'title' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'new_slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9-]+$/'],
            'status' => ['nullable', 'string', 'in:draft,scheduled,published'],
            'published_at' => ['nullable', 'date'],
        ]);

        if (empty($validated['slug']) && empty($validated['id'])) {
            return Response::error('Provide either `slug` or `id` to identify the post.');
        }

        $post = isset($validated['id'])
            ? Post::query()->where('id', $validated['id'])->first()
            : Post::query()->where('slug', $validated['slug'])->first();

        if ($post === null) {
            return Response::error('Post not found.');
        }

        foreach (['title', 'body', 'excerpt', 'published_at'] as $field) {
            if (array_key_exists($field, $validated) && $validated[$field] !== null) {
                $post->{$field} = $validated[$field];
            }
        }

        if (isset($validated['new_slug'])) {
            $post->slug = $validated['new_slug'];
        }
        if (isset($validated['status'])) {
            $post->status = PostStatus::from($validated['status']);
        }

        $post->save();

        $post->load('dataLinks');

        return Response::structured(GetPostTool::serialize($post));
    }

    /** @return array<string, mixed> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'slug' => $schema->string()->description('Existing slug (identifier).'),
            'id' => $schema->integer()->description('Existing id (identifier).'),
            'title' => $schema->string(),
            'body' => $schema->string(),
            'excerpt' => $schema->string(),
            'new_slug' => $schema->string()->description('Rename the slug (lowercase, hyphens).'),
            'status' => $schema->string()->enum(['draft', 'scheduled', 'published']),
            'published_at' => $schema->string()->description('ISO datetime.'),
        ];
    }
}
