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
use LorneQuinn\Blog\Core\Component\Component;
use LorneQuinn\Blog\Core\Component\ComponentRegistry;
use LorneQuinn\Blog\Core\Models\Post;
use LorneQuinn\Blog\Core\Shortcode\ShortcodeParser;

#[IsReadOnly]
#[Description('Dry-run validate a Post body: shortcode syntax, attribute conformity, unresolved components. Does not persist.')]
class ValidatePostTool extends Tool
{
    public function __construct(private readonly ComponentRegistry $components) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'slug' => ['nullable', 'string'],
            'id' => ['nullable', 'integer'],
            'body' => ['nullable', 'string'],
        ]);

        $body = $validated['body'] ?? null;
        if ($body === null) {
            if (empty($validated['slug']) && empty($validated['id'])) {
                return Response::error('Provide `body` directly, or `slug`/`id` of an existing Post.');
            }
            $post = isset($validated['id'])
                ? Post::query()->where('id', $validated['id'])->first()
                : Post::query()->where('slug', $validated['slug'])->first();
            if ($post === null) {
                return Response::error('Post not found.');
            }
            $body = $post->body ?? '';
        }

        /** @var array<int, array{name: string, attrs: array<string, string>}> $tokens */
        $tokens = [];
        $parser = new ShortcodeParser(function (string $name, array $attrs) use (&$tokens): string {
            $tokens[] = ['name' => $name, 'attrs' => $attrs];

            return '';
        });
        $parser->parse($body);

        /** @var array<int, array{shortcode: string, issue: string, detail?: string}> $issues */
        $issues = [];
        foreach ($tokens as $token) {
            $component = $this->components->resolve($token['name']);

            if ($component === null) {
                $issues[] = [
                    'shortcode' => $token['name'],
                    'issue' => 'unknown-component',
                    'detail' => 'No Component registered for this shortcode name.',
                ];

                continue;
            }

            $missing = array_diff($component->requiredAttributes(), array_keys($token['attrs']));
            foreach ($missing as $attr) {
                $issues[] = [
                    'shortcode' => $token['name'],
                    'issue' => 'missing-required-attr',
                    'detail' => "Attribute `{$attr}` is required.",
                ];
            }

            $allowed = array_merge(
                $component->requiredAttributes(),
                array_keys($component->optionalAttributes()),
            );
            $unknown = array_diff(array_keys($token['attrs']), $allowed);
            foreach ($unknown as $attr) {
                $issues[] = [
                    'shortcode' => $token['name'],
                    'issue' => 'unknown-attr',
                    'detail' => "Attribute `{$attr}` not declared by Component.",
                ];
            }
        }

        return Response::structured([
            'ok' => $issues === [],
            'shortcode_count' => count($tokens),
            'issues' => $issues,
            'registered_components' => array_map(
                fn (Component $c): array => [
                    'name' => $c->name(),
                    'data_type' => $c->dataType(),
                    'required' => $c->requiredAttributes(),
                    'optional' => array_keys($c->optionalAttributes()),
                ],
                array_values($this->components->all()),
            ),
        ]);
    }

    /** @return array<string, mixed> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'slug' => $schema->string()->description('Slug of an existing Post to validate.'),
            'id' => $schema->integer()->description('Id of an existing Post to validate.'),
            'body' => $schema->string()->description('Raw body to validate (use instead of slug/id for a dry-run on unsaved content).'),
        ];
    }
}
