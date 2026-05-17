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
use LorneQuinn\Blog\Core\Enums\PostStatus;
use LorneQuinn\Blog\Core\Models\Post;

#[IsReadOnly]
#[Description('List posts, filtered by status / search / date. Returns slim records (id, slug, title, status, published_at).')]
class ListPostsTool extends Tool
{
    public function handle(Request $request): ResponseFactory
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', 'in:draft,scheduled,published'],
            'search' => ['nullable', 'string', 'max:200'],
            'since' => ['nullable', 'date'],
            'until' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $query = Post::query()->orderByDesc('updated_at');

        if (isset($validated['status'])) {
            $query->where('status', PostStatus::from($validated['status']));
        }
        if (isset($validated['search']) && $validated['search'] !== '') {
            $term = '%'.$validated['search'].'%';
            $query->where(function ($q) use ($term): void {
                $q->where('title', 'like', $term)->orWhere('body', 'like', $term);
            });
        }
        if (isset($validated['since'])) {
            $query->where('published_at', '>=', $validated['since']);
        }
        if (isset($validated['until'])) {
            $query->where('published_at', '<=', $validated['until']);
        }

        $perPage = (int) ($validated['per_page'] ?? 25);
        $page = (int) ($validated['page'] ?? 1);
        $paginator = $query->paginate(perPage: $perPage, page: $page);

        $posts = collect($paginator->items())
            ->map(fn (Post $p): array => [
                'id' => $p->id,
                'slug' => $p->slug,
                'title' => $p->title,
                'status' => $p->status->value,
                'published_at' => $p->published_at?->toIso8601String(),
                'updated_at' => $p->updated_at->toIso8601String(),
            ])
            ->all();

        return Response::structured([
            'posts' => $posts,
            'page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
        ]);
    }

    /** @return array<string, mixed> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->string()
                ->enum(['draft', 'scheduled', 'published'])
                ->description('Filter by status.'),
            'search' => $schema->string()->description('Substring match against title and body.'),
            'since' => $schema->string()->description('ISO date — published on or after.'),
            'until' => $schema->string()->description('ISO date — published on or before.'),
            'per_page' => $schema->integer()->description('Page size (default 25, max 100).'),
            'page' => $schema->integer()->description('Page number (1-indexed).'),
        ];
    }
}
