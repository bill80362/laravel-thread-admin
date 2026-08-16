<div class="space-y-4">
    <p class="text-gray-600 dark:text-gray-400">MCP（Model Context Protocol）是一種讓 AI 工具（如 ChatGPT、Claude Desktop）可以直接操作本系統的協定。系統支援兩種連線方式：</p>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
            <div class="flex items-center gap-2">
                <x-filament::icon icon="heroicon-o-computer-desktop" class="w-5 h-5 text-primary-600 dark:text-primary-400" />
                <h4 class="font-semibold text-gray-900 dark:text-white">本地模式（開發者）</h4>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">在本機直接啟動，無需網路。開發者或本機 AI 工具使用。</p>
        </div>
        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
            <div class="flex items-center gap-2">
                <x-filament::icon icon="heroicon-o-globe-alt" class="w-5 h-5 text-primary-600 dark:text-primary-400" />
                <h4 class="font-semibold text-gray-900 dark:text-white">HTTP 模式（遠端）</h4>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">透過網路連線，使用 OAuth 2.1 認證。適合遠端 AI 服務使用。</p>
        </div>
    </div>

    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
        <div class="flex items-center gap-2 mb-3">
            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-bold">C</span>
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Claude Desktop 設定步驟</h3>
        </div>
        <div class="bg-gray-50 dark:bg-gray-700/30 rounded-lg p-4">
            <p class="text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">1. 開啟 Claude Desktop 設定檔</p>
            <pre class="bg-gray-900 text-gray-100 text-xs p-3 rounded overflow-x-auto"><code># macOS: ~/Library/Application Support/Claude/claude_desktop_config.json
# Windows: %APPDATA%\Claude\claude_desktop_config.json</code></pre>
            <p class="text-sm font-medium text-gray-700 dark:text-gray-200 mt-3 mb-1">2. 加入 MCP 伺服器設定</p>
            <pre class="bg-gray-900 text-gray-100 text-xs p-3 rounded overflow-x-auto"><code>{
  "mcpServers": {
    "threads": {
      "command": "php",
      "args": ["/你的專案路徑/artisan", "mcp:start", "threads"]
    }
  }
}</code></pre>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-3">3. 將 <code class="bg-gray-200 dark:bg-gray-600 px-1 rounded text-xs">/你的專案路徑/artisan</code> 換成實際的專案路徑。重啟 Claude Desktop 即可使用。</p>
        </div>
    </div>

    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
        <div class="flex items-center gap-2 mb-3">
            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-bold">G</span>
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">ChatGPT 設定步驟</h3>
        </div>
        <div class="bg-gray-50 dark:bg-gray-700/30 rounded-lg p-4">
            <p class="text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">1. 開啟 ChatGPT 的 MCP 設定</p>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">在 ChatGPT 中點擊「設定」→「MCP 伺服器」→「新增伺服器」</p>
            <p class="text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">2. 選擇連線類型</p>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">選擇「本機指令」（Stdio）類型</p>
            <p class="text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">3. 填入以下資訊</p>
            <div class="bg-gray-100 dark:bg-gray-600/30 rounded p-3 text-sm text-gray-700 dark:text-gray-300 mt-2">
                <p><strong>名稱：</strong>Threads 管理</p>
                <p><strong>指令：</strong><code>php</code></p>
                <p><strong>參數：</strong><code>/你的專案路徑/artisan mcp:start threads</code></p>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-3">4. 儲存後即可在對話中呼叫本系統的功能（查詢帳號、建立排程貼文、查詢回覆等）。</p>
        </div>
    </div>

    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mt-4">
        <p class="text-sm text-blue-800 dark:text-blue-200"><strong>提示：</strong>如果需要遠端（HTTP）模式連線，請先在「MCP 控管」頁面管理 OAuth token。</p>
    </div>
</div>
