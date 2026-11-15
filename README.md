# blog-mcp

MCP server tools for the LQ blog suite. This is the **editor surface** — Claude reads and writes Posts here. No web admin UI, ever.

Built on [`laravel/mcp`](https://github.com/laravel/mcp).

## Install

```bash
composer require lornequinn/blog-mcp
```

This pulls in `lornequinn/blog-core` (required) and `laravel/mcp` (the protocol layer).

## Register the server

Publish the `routes/ai.php` file if you haven't already:

```bash
php artisan vendor:publish --tag=ai-routes
```

Then register `BlogServer` in `routes/ai.php`:

```php
use Laravel\Mcp\Facades\Mcp;
use LorneQuinn\Blog\Mcp\BlogServer;

Mcp::local('blog', BlogServer::class);
```

## Run the server over stdio

```bash
php artisan mcp:start blog
```

That's the local-dev transport. Point Claude Code / Claude Desktop at this command in your MCP host config.

### Example Claude Code MCP config

`.mcp.json` or your global `~/.claude.json` (project-scoped form):

```json
{
  "mcpServers": {
    "blog": {
      "command": "php",
      "args": ["artisan", "mcp:start", "blog"],
      "cwd": "/path/to/your/consumer/app"
    }
  }
}
```

## Run the server over HTTP (deployed sites)

To edit a deployed site, serve `BlogServer` over HTTP instead. **This endpoint can create, publish and delete posts, so never expose it without auth.** `Mcp::web()` returns the route, so add your app's auth middleware to it:

```php
use Laravel\Mcp\Facades\Mcp;
use LorneQuinn\Blog\Mcp\BlogServer;

Mcp::web('/mcp/blog', BlogServer::class)->middleware('auth:sanctum');
```

Auth is your app's job. blog-mcp ships no users or tokens. The simplest path is [Sanctum](https://laravel.com/docs/sanctum) personal access tokens:

```bash
php artisan install:api          # installs Sanctum
```

Add `Laravel\Sanctum\HasApiTokens` to your `User` model, then issue a token for the account Claude edits as:

```php
$user->createToken('claude')->plainTextToken;
```

Without a valid token the endpoint returns `401`. With one, MCP works as normal. `laravel/mcp` also ships OAuth discovery (`Mcp::oauthRoutes()`, Passport-backed) if you need it, but a bearer token is enough for a single operator.

### Example Claude Code MCP config (HTTP)

```json
{
  "mcpServers": {
    "blog": {
      "type": "http",
      "url": "https://your-site.example/mcp/blog",
      "headers": {
        "Authorization": "Bearer ${BLOG_MCP_TOKEN}"
      }
    }
  }
}
```

Keep the token in an environment variable, not in a committed `.mcp.json`.

## Tools shipped at v1 (Phase 2)

| Tool | Purpose |
|------|---------|
| `list-posts-tool` | Filter by status, search, date range; paginated. |
| `get-post-tool` | Fetch by slug or id with `dataLinks` eager-loaded. |
| `create-post-tool` | Auto-slug from title. Status defaults to `draft`. |
| `update-post-tool` | Partial update. Identify by slug or id. |
| `delete-post-tool` | Hard delete at v1 (soft-delete deferred until Post grows `SoftDeletes`). |
| `publish-post-tool` | Transition to published; stamp `published_at`. |
| `unpublish-post-tool` | Back to draft; clear `published_at`. |
| `describe-data-types-tool` | Introspection of registered DataTypes. Returns `[]` at v1 — Phase 4 lights it up. |
| `validate-post-tool` | Dry-run validate body: shortcode syntax, unknown components, missing/unknown attributes. |

DataType-specific tools (`attach-data`, `update-data`, `detach-data`, `list-components`) ship in **Phase 4** when DataTypes start landing.

## Validation discipline

Every write tool re-validates server-side via `$request->validate([...])`. Per the spec, write tools are server-side authoritative; the MCP `inputSchema` is a hint to the client, not a trust boundary.

## Testing

```bash
composer install
./vendor/bin/pest
./vendor/bin/phpstan analyse
```

Tests use `BlogServer::tool(MyTool::class, [...])` against `laravel/mcp`'s `TestResponse` harness — no real stdio transport is booted in CI.
