<?php

declare(strict_types=1);

use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use LorneQuinn\Blog\Mcp\BlogServer;
use LorneQuinn\Blog\Mcp\Tools\CreatePostTool;
use LorneQuinn\Blog\Mcp\Tools\DeletePostTool;
use LorneQuinn\Blog\Mcp\Tools\DescribeDataTypesTool;
use LorneQuinn\Blog\Mcp\Tools\GetPostTool;
use LorneQuinn\Blog\Mcp\Tools\ListPostsTool;
use LorneQuinn\Blog\Mcp\Tools\PublishPostTool;
use LorneQuinn\Blog\Mcp\Tools\UnpublishPostTool;
use LorneQuinn\Blog\Mcp\Tools\UpdatePostTool;
use LorneQuinn\Blog\Mcp\Tools\ValidatePostTool;

it('exposes the expected server identity', function (): void {
    $reflection = new ReflectionClass(BlogServer::class);
    $nameAttr = $reflection->getAttributes(Name::class);
    $versionAttr = $reflection->getAttributes(Version::class);

    expect($nameAttr)->toHaveCount(1)
        ->and($nameAttr[0]->newInstance()->value)->toBe('blog')
        ->and($versionAttr)->toHaveCount(1);
});

it('declares the expected Phase 2 tools', function (): void {
    $reflection = new ReflectionClass(BlogServer::class);
    $tools = $reflection->getDefaultProperties()['tools'] ?? [];

    expect($tools)->toEqual([
        ListPostsTool::class,
        GetPostTool::class,
        CreatePostTool::class,
        UpdatePostTool::class,
        DeletePostTool::class,
        PublishPostTool::class,
        UnpublishPostTool::class,
        DescribeDataTypesTool::class,
        ValidatePostTool::class,
    ]);
});

it('routes a tool call through the BlogServer test harness', function (): void {
    BlogServer::tool(DescribeDataTypesTool::class, [])
        ->assertOk()
        ->assertName('describe-data-types-tool');
});
