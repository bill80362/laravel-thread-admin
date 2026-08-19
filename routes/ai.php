<?php

use App\Mcp\Servers\ThreadsMcpServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::oauthRoutes();

Mcp::web('/mcp/threads', ThreadsMcpServer::class)
    ->middleware('auth:api');
