<?php

namespace Tests\Concerns;

use Illuminate\Testing\TestResponse;

/**
 * Calls the MCP endpoint the way claude.ai does: JSON-RPC over POST /mcp with the secret.
 */
trait CallsMcp
{
    protected string $mcpSecret = 'test-secret-0123456789abcdef0123456789abcdef';

    protected function setUpCallsMcp(): void
    {
        config(['services.mcp.secret' => $this->mcpSecret]);
    }

    protected function rpc(string $method, array $params = [], ?string $secret = null, string $uri = '/mcp'): TestResponse
    {
        return $this->withHeaders(array_filter([
            'Authorization' => $uri === '/mcp' ? 'Bearer '.($secret ?? $this->mcpSecret) : null,
            'Accept' => 'application/json, text/event-stream',
        ]))->postJson($uri, ['jsonrpc' => '2.0', 'id' => 1, 'method' => $method] + ($params ? ['params' => $params] : []));
    }

    /** A tool's result as an array; asserts it worked unless $error */
    protected function tool(string $name, array $arguments = [], bool $error = false): array
    {
        config(['services.mcp.secret' => $this->mcpSecret]);
        $response = $this->rpc('tools/call', ['name' => $name, 'arguments' => (object) $arguments])->assertOk();
        $result = $response->json('result');
        $this->assertNotNull($result, 'No result: '.$response->getContent());
        $text = $result['content'][0]['text'] ?? '';
        $this->assertSame($error, (bool) ($result['isError'] ?? false), "$name: ".$text);

        return $error ? ['error' => $text] : (json_decode($text, true) ?? []);
    }
}
