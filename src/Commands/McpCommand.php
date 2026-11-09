<?php

namespace Nicro\Nui\Commands;

use Illuminate\Console\Command;
use Nicro\Nui\Mcp\Server;

/**
 * Starts the nUI MCP server over stdio, for assistants such as Claude Code:
 * claude mcp add nui -- php artisan nui:mcp
 */
class McpCommand extends Command
{
    protected $signature = 'nui:mcp';

    protected $description = 'Inicia o servidor MCP do nUI (stdio) com componentes, exemplos e tokens da versão instalada';

    public function handle(): int
    {
        $server = new Server;
        $input = fopen('php://stdin', 'rb');
        $output = fopen('php://stdout', 'wb');

        while (($line = fgets($input)) !== false) {
            $response = $server->handleLine($line);

            if ($response !== null) {
                fwrite($output, $response."\n");
                fflush($output);
            }
        }

        return self::SUCCESS;
    }
}
