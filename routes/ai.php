<?php

use App\Mcp\Servers\ThreadsMcpServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::oauthRoutes();

Mcp::local('threads', ThreadsMcpServer::class);

Mcp::web('/mcp/threads', ThreadsMcpServer::class)
    ->middleware('auth:api');
