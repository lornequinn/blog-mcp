<?php

declare(strict_types=1);

namespace LorneQuinn\Blog\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use LorneQuinn\Blog\Core\Models\Post;

#[IsReadOnly]
#[Description('Fetch a single Post by slug or id, with attached DataType links eager-loaded.')]
class GetPostTool extends Tool
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
            ? Post::query()->with('dataLinks')->where('id', $validated['id'])->first()
            : Post::query()->with('dataLinks')->where('slug', $validated['slug'])->first();

        if ($post === null) {
            return Response::error('Post not found.');
        }

        return Response::structured(self::serialize($post));
    }

    /** @return array<string, mixed> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'slug' => $schema->string()->description('Slug to look up.'),
            'id' => $schema->integer()->description('Primary key to look up.'),
        ];
    }

    /** @return array<string, mixed> */
    public static function serialize(Post $post): array
    {
        return [
            'id' => $post->id,
            'slug' => $post->slug,
            'title' => $post->title,
            'body' => $post->body,
            'excerpt' => $post->excerpt,
            'status' => $post->status->value,
            'published_at' => $post->published_at?->toIso8601String(),
            'created_at' => $post->created_at->toIso8601String(),
            'updated_at' => $post->updated_at->toIso8601String(),
            'data_links' => $post->dataLinks->map(fn ($link): array => [
                'id' => $link->id,
                'data_type' => $link->data_type ?? null,
            ])->all(),
        ];
    }
}
