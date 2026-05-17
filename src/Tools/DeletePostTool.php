<?php

declare(strict_types=1);

namespace LorneQuinn\Blog\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use LorneQuinn\Blog\Core\Models\Post;

#[Description('Delete a Post. Hard delete at v1; soft-delete configurability deferred until SoftDeletes lands on Post.')]
class DeletePostTool extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'slug' => ['nullable', 'string'],
            'id' => ['nullable', 'integer'],
        ]);

        if (empty($validated['slug']) && empty($validated['id'])) {
            return Response::error('Provide either `slug` or `id`.');
        }

        $post = isset($validated['id'])
            ? Post::query()->where('id', $validated['id'])->first()
            : Post::query()->where('slug', $validated['slug'])->first();

        if ($post === null) {
            return Response::error('Post not found.');
        }

        $deletedSlug = $post->slug;
        $deletedId = $post->id;
        $post->delete();

        return Response::structured([
            'deleted' => true,
            'id' => $deletedId,
            'slug' => $deletedSlug,
        ]);
    }

    /** @return array<string, mixed> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'slug' => $schema->string()->description('Slug of the post to delete.'),
            'id' => $schema->integer()->description('Id of the post to delete.'),
        ];
    }
}
