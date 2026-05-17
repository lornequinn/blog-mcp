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

#[Description('Unpublish a Post — transitions status back to draft and clears published_at.')]
class UnpublishPostTool extends Tool
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

        $post->status = PostStatus::Draft;
        $post->published_at = null;
        $post->save();

        $post->load('dataLinks');

        return Response::structured(GetPostTool::serialize($post));
    }

    /** @return array<string, mixed> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'slug' => $schema->string(),
            'id' => $schema->integer(),
        ];
    }
}
