<?php

namespace App\Http\Middleware;

use App\Services\Activity;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * The MCP endpoint: needs MCP_SECRET, as "Authorization: Bearer <secret>" or in the address
 * (/mcp/<secret>, for connectors that cannot send headers). HTTPS only outside local, and
 * every call is written to storage/logs/mcp-*.log.
 */
class McpAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = (string) config('services.mcp.secret');

        if (! app()->isLocal() && ! app()->runningUnitTests() && ! $request->secure()) {
            return response()->json(['error' => 'HTTPS is required.'], 403);
        }

        $given = (string) ($request->route('secret') ?? $request->bearerToken() ?? '');
        if (strlen($secret) < 32 || ! hash_equals($secret, $given)) {
            Log::channel('mcp')->warning('Rejected MCP call', ['ip' => $request->ip()]);

            return response()->json(['error' => 'Unauthorized.'], 401);
        }

        Activity::$actor = 'mcp '.$request->ip();
        $started = microtime(true);
        $response = $next($request);

        $body = json_decode($request->getContent(), true);
        Log::channel('mcp')->info('MCP call', [
            'ip' => $request->ip(),
            'method' => $body['method'] ?? null,
            'tool' => $body['params']['name'] ?? null,
            'arguments' => isset($body['params']['arguments']) ? array_keys($body['params']['arguments']) : null,
            'status' => $response->getStatusCode(),
            'ms' => (int) ((microtime(true) - $started) * 1000),
        ]);

        return $response;
    }
}
