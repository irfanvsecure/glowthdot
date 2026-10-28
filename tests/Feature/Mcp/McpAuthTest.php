<?php

namespace Tests\Feature\Mcp;

use App\Models\ActivityLog;
use Tests\Concerns\CallsMcp;
use Tests\TestCase;

class McpAuthTest extends TestCase
{
    use CallsMcp;

    public function test_calls_without_the_secret_are_refused(): void
    {
        config(['services.mcp.secret' => $this->mcpSecret]);

        $this->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping'])->assertStatus(401);
        $this->rpc('ping', secret: 'wrong-secret-0123456789abcdef0123456789')->assertStatus(401);
        $this->rpc('ping', uri: '/mcp/wrong-secret-0123456789abcdef0123456789')->assertStatus(401);
    }

    public function test_the_server_is_off_without_a_long_enough_secret(): void
    {
        config(['services.mcp.secret' => 'short']);

        $this->rpc('ping', secret: 'short')->assertStatus(401);
        config(['services.mcp.secret' => null]);
        $this->rpc('ping', secret: '')->assertStatus(401);
    }

    public function test_bearer_header_and_secret_address_both_work(): void
    {
        config(['services.mcp.secret' => $this->mcpSecret]);

        $this->rpc('ping')->assertOk()->assertJsonPath('jsonrpc', '2.0');
        $this->rpc('ping', uri: '/mcp/'.$this->mcpSecret)->assertOk()->assertJsonPath('jsonrpc', '2.0');
    }

    public function test_initialize_and_every_tool_is_listed_with_a_schema(): void
    {
        config(['services.mcp.secret' => $this->mcpSecret]);

        $this->rpc('initialize', ['protocolVersion' => '2025-06-18', 'capabilities' => (object) [], 'clientInfo' => ['name' => 'test', 'version' => '1']])
            ->assertOk()->assertJsonPath('result.serverInfo.name', 'Glowthdot.com');

        $tools = collect($this->rpc('tools/list')->assertOk()->json('result.tools'));
        $this->assertCount(58, $tools);
        foreach (['list_pages', 'update_page_section', 'replace_text', 'create_blog', 'schedule_blog', 'upload_image', 'replace_image',
            'update_seo', 'audit_seo', 'generate_schema', 'check_broken_links', 'save_redirect', 'validate_sitemap_robots',
            'update_settings', 'list_trash', 'purge_item', 'activity_log', 'clear_cache', 'audit_performance', 'get_site_info'] as $name) {
            $tool = $tools->firstWhere('name', $name);
            $this->assertNotNull($tool, "$name is not listed");
            $this->assertNotEmpty($tool['description']);
            $this->assertSame('object', $tool['inputSchema']['type']);
        }
        // No tools for SQL, shell commands, files or .env
        $this->assertEmpty($tools->pluck('name')->filter(fn ($n) => preg_match('/sql|shell|exec|command|file_write|env/i', $n)));
    }

    public function test_unknown_tools_and_bad_input_come_back_as_errors(): void
    {
        config(['services.mcp.secret' => $this->mcpSecret]);

        // A JSON-RPC error: unknown tool
        $this->rpc('tools/call', ['name' => 'drop_database', 'arguments' => (object) []])->assertStatus(400)->assertJsonPath('error.code', -32602);
        $error = $this->tool('create_page', [], error: true)['error'];
        $this->assertStringContainsString('title', $error);
    }

    public function test_calls_are_logged(): void
    {
        $this->tool('get_site_info');

        $this->assertDatabaseCount('activity_log', 0); // reads are not content changes
        $this->tool('update_settings', ['settings' => ['whatsapp_button_text' => 'Chat with us']]);
        $this->assertDatabaseHas('activity_log', ['action' => 'settings.update', 'subject_label' => 'whatsapp_button_text']);
        $this->assertStringStartsWith('mcp ', ActivityLog::latest('id')->first()->actor);
    }
}
