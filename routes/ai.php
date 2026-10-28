<?php

use App\Http\Middleware\McpAuth;
use App\Mcp\Servers\SiteServer;
use Laravel\Mcp\Facades\Mcp;

// The site's MCP server (claude.ai custom connector). Not in the "web" group: no session,
// cookies or CSRF. The secret goes in the Authorization header or in the address.
Mcp::web('/mcp', SiteServer::class)->middleware([McpAuth::class, 'throttle:mcp']);
Mcp::web('/mcp/{secret}', SiteServer::class)->middleware([McpAuth::class, 'throttle:mcp'])->where('secret', '[A-Za-z0-9_-]{32,128}');
