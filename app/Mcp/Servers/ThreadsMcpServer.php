<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\CreatePostTool;
use App\Mcp\Tools\CreateReplyTool;
use App\Mcp\Tools\GetPostTool;
use App\Mcp\Tools\ListAccountsTool;
use App\Mcp\Tools\ListPostsTool;
use App\Mcp\Tools\ListRepliesTool;
use App\Mcp\Tools\ReplyToReplyTool;
use App\Mcp\Tools\UpdateReplyStatusTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\Tool;

#[Name('Threads 管理伺服器')]
#[Version('1.0.0')]
#[Instructions('此伺服器提供 Threads 帳號查詢、排程貼文與回覆功能。使用前請先在後台介面綁定 Threads 帳號。帳號僅供讀取，不提供新增／修改／刪除。')]
class ThreadsMcpServer extends Server
{
    /**
     * @var array<int, class-string<Tool>>
     */
    protected array $tools = [
        ListAccountsTool::class,
        CreatePostTool::class,
        ListPostsTool::class,
        GetPostTool::class,
        ListRepliesTool::class,
        CreateReplyTool::class,
        ReplyToReplyTool::class,
        UpdateReplyStatusTool::class,
    ];
}
