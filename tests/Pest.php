<?php

declare(strict_types=1);

use Illuminate\Testing\Fluent\AssertableJson;
use LorneQuinn\Blog\Mcp\Tests\TestCase;

uses(TestCase::class)->in('Feature');

/**
 * Wrap a fluent-JSON assertion chain so it matches the
 * Closure(AssertableJson): bool signature `assertStructuredContent` expects.
 */
function structuredJson(Closure $assertions): Closure
{
    return static function (AssertableJson $json) use ($assertions): bool {
        $assertions($json);

        return true;
    };
}
