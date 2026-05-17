<?php

declare(strict_types=1);

use LorneQuinn\Blog\Mcp\BlogServer;
use LorneQuinn\Blog\Mcp\Tools\DescribeDataTypesTool;

it('returns an empty list when no DataTypes are registered (v1 baseline)', function (): void {
    BlogServer::tool(DescribeDataTypesTool::class, [])
        ->assertOk()
        ->assertStructuredContent(['data_types' => []]);
});
