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
use LorneQuinn\Blog\Core\Component\ComponentRegistry;
use LorneQuinn\Blog\Core\DataType\DataType;
use LorneQuinn\Blog\Core\DataType\DataTypeRegistry;

#[IsReadOnly]
#[Description('Introspection: registered DataTypes with their model, validation rules, and primary Component. Empty at v1 until Phase 4 registers real DataTypes.')]
class DescribeDataTypesTool extends Tool
{
    public function __construct(
        private readonly DataTypeRegistry $dataTypes,
        private readonly ComponentRegistry $components,
    ) {}

    public function handle(Request $request): ResponseFactory
    {
        $types = array_map(
            fn (DataType $t): array => [
                'name' => $t->name(),
                'model' => $t->model(),
                'rules' => $t->rules(),
                'primary_component' => $t->primaryComponent(),
                'components' => array_keys($this->components->forDataType($t->name())),
            ],
            array_values($this->dataTypes->all()),
        );

        return Response::structured(['data_types' => $types]);
    }

    /** @return array<string, mixed> */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
