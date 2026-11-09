<?php

namespace Nicro\Nui\Mcp;

use InvalidArgumentException;

/**
 * Raised for bad tool or prompt arguments; the server answers with JSON-RPC error -32602.
 */
class InvalidParams extends InvalidArgumentException {}
