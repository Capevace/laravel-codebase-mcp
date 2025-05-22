<?php

namespace Mateffy\LaravelCodebaseMcp\Commands;

use Illuminate\Console\Command;
use Mateffy\LaravelCodebaseMcp\Tools\QueryClasses;
use Mateffy\LaravelCodebaseMcp\Tools\QueryModels;
use Mateffy\LaravelCodebaseMcp\Tools\QueryRoutes;
use Mateffy\LaravelCodebaseMcp\Tools\QueryViews;
use PhpMcp\Server\Server;
use PhpMcp\Server\Transports\StdioServerTransport;

class IntrospectMcpCommand extends Command
{
	protected $signature = 'introspect:mcp';

	protected $description = 'Run the MCP server for introspection using STDIO transport';

	public function handle(): void
	{
        try {
            // 1. Build the Server configuration
            $server = Server::make()
                ->withServerInfo('Laravel', '1.0.2')
                ->withTool(
                    [QueryViews::class, 'queryViews'],
                    name: QueryViews::name(),
                    description: QueryViews::description(),
                )
                ->withTool(
                    [QueryModels::class, 'queryModels'],
                    name: QueryModels::name(),
                    description: QueryModels::description(),
                )
                ->withTool(
                    [QueryRoutes::class, 'queryRoutes'],
                    name: QueryRoutes::name(),
                    description: QueryRoutes::description(),
                )
                ->withTool(
                    [QueryClasses::class, 'queryClasses'],
                    name: QueryClasses::name(),
                    description: QueryClasses::description(),
                )
                ->build();

            // 3. Create the Stdio Transport
            $transport = new StdioServerTransport();

            // 4. Start Listening (BLOCKING call)
            $server->listen($transport);

            exit(0);

        } catch (\Throwable $e) {
            report($e);

            fwrite(STDERR, "[MCP SERVER CRITICAL ERROR]\n" . $e . "\n");
            exit(1);
        }
	}
}
