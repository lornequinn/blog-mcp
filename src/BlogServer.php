<?php

declare(strict_types=1);

namespace LorneQuinn\Blog\Mcp;

use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\Tool;
use LorneQuinn\Blog\Mcp\Tools\CreatePostTool;
use LorneQuinn\Blog\Mcp\Tools\DeletePostTool;
use LorneQuinn\Blog\Mcp\Tools\DescribeDataTypesTool;
use LorneQuinn\Blog\Mcp\Tools\GetPostTool;
use LorneQuinn\Blog\Mcp\Tools\ListPostsTool;
use LorneQuinn\Blog\Mcp\Tools\PublishPostTool;
use LorneQuinn\Blog\Mcp\Tools\UnpublishPostTool;
use LorneQuinn\Blog\Mcp\Tools\UpdatePostTool;
use LorneQuinn\Blog\Mcp\Tools\ValidatePostTool;

#[Name('blog')]
#[Version('0.1.0')]
#[Description('Editor surface for the LQ blog suite. Read, write, publish, and validate Posts.')]
class BlogServer extends Server
{
    /** @var array<int, class-string<Tool>> */
    protected array $tools = [
        ListPostsTool::class,
        GetPostTool::class,
        CreatePostTool::class,
        UpdatePostTool::class,
        DeletePostTool::class,
        PublishPostTool::class,
        UnpublishPostTool::class,
        DescribeDataTypesTool::class,
        ValidatePostTool::class,
    ];
}
