<?php

namespace App\Mcp;

use App\Support\ContentVersion;
use Closure;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

/**
 * One MCP tool of the site: name, description, input schema, validation rules and a handler
 * that gets the validated input and returns an array (sent back as JSON). The tool sets in
 * app/Mcp/Tools build these.
 */
class SiteTool extends Tool
{
    /**
     * @param  Closure(JsonSchema): array  $schema
     * @param  array<string, mixed>  $rules  Laravel validation rules for the input
     * @param  Closure(array): array  $handler
     * @param  bool  $writes  changes content: caches are refreshed after it runs
     */
    public function __construct(
        string $name,
        string $description,
        private Closure $schema,
        private array $rules,
        private Closure $handler,
        private bool $writes = false,
    ) {
        $this->name = $name;
        $this->description = $description;
    }

    public function schema(JsonSchema $schema): array
    {
        return ($this->schema)($schema);
    }

    public function handle(Request $request): Response
    {
        $input = $this->rules ? $request->validate($this->rules) : [];

        try {
            $result = ($this->handler)($input);
        } catch (ToolError $e) {
            return Response::error($e->getMessage());
        }

        if ($this->writes) {
            ContentVersion::bump();
        }

        return Response::json($result);
    }

    /** For a tool's annotations: does it change anything */
    public function writes(): bool
    {
        return $this->writes;
    }
}
