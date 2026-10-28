<?php

namespace App\Mcp;

use RuntimeException;

/**
 * A request the tool cannot carry out (not found, slug taken, image in use ...). The message
 * goes back to the caller as the tool's error.
 */
class ToolError extends RuntimeException {}
