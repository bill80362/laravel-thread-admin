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

    {{-- ===== HTTP 模式設定步驟 ===== --}}
    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
        <div class="flex items-center gap-2 mb-3">
            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-400 text-xs font-bold">H</span>
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">HTTP 模式設定步驟</h3>
        </div>

        {{-- 連線網址 --}}
        <div class="bg-gray-50 dark:bg-gray-700/30 rounded-lg p-4 mb-4">
            <p class="text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">1. 確認對外連線網址</p>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">HTTP 模式需要一個遠端 AI 客戶端能連到的公開網址。系統的所有端點都由 <code class="bg-gray-200 dark:bg-gray-600 px-1 rounded text-xs">APP_URL</code> 環境變數決定，請確認該網址可從外部存取（正式網域、Cloudflare Tunnel、ngrok 皆可）。</p>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">以下是遠端 AI 客戶端會用到的端點（以 <code class="bg-gray-200 dark:bg-gray-600 px-1 rounded text-xs">https://你的後台網址</code> 為例）：</p>
            <div class="bg-gray-100 dark:bg-gray-600/30 rounded p-3 text-sm text-gray-700 dark:text-gray-300 overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="border-b border-gray-300 dark:border-gray-600">
                            <th class="text-left py-1 pr-3 font-semibold">用途</th>
                            <th class="text-left py-1 pr-3 font-semibold">端點</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <td class="py-1 pr-3">MCP 入口</td>
                            <td class="py-1"><code>/mcp/threads</code></td>
                        </tr>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <td class="py-1 pr-3">授權伺服器 metadata</td>
                            <td class="py-1"><code>/.well-known/oauth-authorization-server</code></td>
                        </tr>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <td class="py-1 pr-3">受保護資源 metadata</td>
                            <td class="py-1"><code>/.well-known/oauth-protected-resource</code></td>
                        </tr>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <td class="py-1 pr-3">客戶端註冊</td>
                            <td class="py-1"><code>POST /oauth/register</code></td>
                        </tr>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <td class="py-1 pr-3">授權頁</td>
                            <td class="py-1"><code>/oauth/authorize</code></td>
                        </tr>
                        <tr>
                            <td class="py-1 pr-3">Token 換發</td>
                            <td class="py-1"><code>POST /oauth/token</code></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- OAuth 授權流程 --}}
        <div class="bg-gray-50 dark:bg-gray-700/30 rounded-lg p-4 mb-4">
            <p class="text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">2. 了解 OAuth 授權流程</p>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">首次連線時，AI 客戶端會自動完成以下 OAuth 2.1 授權流程。你只需要在瀏覽器彈出時登入並點擊「允許」即可。</p>

            {{-- 純文字流程圖 --}}
            <div class="bg-white dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-700 p-3 mb-3 overflow-x-auto">
                <pre class="text-xs text-gray-700 dark:text-gray-300 leading-relaxed">┌──────────────┐         ┌──────────────────┐         ┌──────────┐         ┌──────────┐
│ 遠端 AI 客戶端 │         │  你的 Laravel 後台  │         │   瀏覽器  │         │  你(使用者)│
│ (Claude/ChatGPT)│         │                  │         │          │         │          │
└──────┬───────┘         └────────┬─────────┘         └────┬─────┘         └────┬─────┘
       │ 1. 連線 POST /mcp/threads │                        │                    │
       │─────────────────────────▶│                        │                    │
       │ 2. 回 401 要求認證         │                        │                    │
       │◀─────────────────────────│                        │                    │
       │ 3. 讀取 .well-known metadata │                     │                    │
       │─────────────────────────▶│                        │                    │
       │ 4. POST /oauth/register 自動註冊                    │                    │
       │─────────────────────────▶│                        │                    │
       │ 5. 開啟授權頁 /oauth/authorize                      │                    │
       │──────────────────────────────────────────────────▶│                    │
       │                          │     6. 輸入後台帳密登入    │                    │
       │                          │◀────────────────────────│                    │
       │                          │     7. 點「允許」          │                    │
       │                          │────────────────────────▶│                    │
       │ 8. 回傳授權碼             │                        │                    │
       │◀──────────────────────────────────────────────────│                    │
       │ 9. POST /oauth/token 換 access token               │                    │
       │─────────────────────────▶│                        │                    │
       │ 10. 帶著 token 呼叫 MCP 工具                        │                    │
       │─────────────────────────▶│                        │                    │</pre>
            </div>

            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">步驟說明：</p>
            <ol class="list-decimal list-inside text-sm text-gray-600 dark:text-gray-400 space-y-1 ml-2">
                <li>AI 客戶端連線到 MCP 入口，伺服器回應需要認證</li>
                <li>客戶端自動讀取 <code class="bg-gray-200 dark:bg-gray-600 px-1 rounded text-xs">.well-known</code> metadata 發現授權端點</li>
                <li>客戶端自動註冊（<code class="bg-gray-200 dark:bg-gray-600 px-1 rounded text-xs">POST /oauth/register</code>），無需手動申請 client_id</li>
                <li>客戶端跳出瀏覽器，導向授權頁</li>
                <li><strong class="text-amber-700 dark:text-amber-300">在授權頁使用「後台登入帳號」登入</strong>（非 Threads 帳號、非 Meta 帳號）</li>
                <li>點擊「允許」授權 AI 客戶端存取 MCP 功能</li>
                <li>客戶端自動換發 access token，完成連線</li>
            </ol>
        </div>
    </div>

    {{-- ===== Claude Desktop 設定步驟 ===== --}}
    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
        <div class="flex items-center gap-2 mb-3">
            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-bold">C</span>
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Claude Desktop 設定步驟</h3>
        </div>

        {{-- 本地模式 --}}
        <div class="bg-gray-50 dark:bg-gray-700/30 rounded-lg p-4 mb-4">
            <p class="text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">本地模式（Stdio）</p>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">適用於 Claude Desktop 與伺服器在同一台電腦上的情況。</p>
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

        {{-- 遠端 HTTP 模式 --}}
        <div class="bg-gray-50 dark:bg-gray-700/30 rounded-lg p-4">
            <p class="text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">遠端 HTTP 模式</p>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">適用於 Claude Desktop 與伺服器在不同電腦上，或使用遠端伺服器的情況。</p>
            <p class="text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">1. 開啟 Claude Desktop 設定檔（同上）</p>
            <p class="text-sm font-medium text-gray-700 dark:text-gray-200 mt-3 mb-1">2. 加入遠端 MCP 伺服器設定</p>
            <pre class="bg-gray-900 text-gray-100 text-xs p-3 rounded overflow-x-auto"><code>{
  "mcpServers": {
    "threads": {
      "type": "http",
      "url": "https://你的後台網址/mcp/threads"
    }
  }
}</code></pre>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-3">3. 將 <code class="bg-gray-200 dark:bg-gray-600 px-1 rounded text-xs">https://你的後台網址</code> 換成實際的後台網址。重啟 Claude Desktop。</p>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">4. 首次連線時，Claude Desktop 會自動跳出瀏覽器進行 OAuth 授權（詳見上方「HTTP 模式設定步驟」）。</p>
        </div>
    </div>

    {{-- ===== ChatGPT 設定步驟 ===== --}}
    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
        <div class="flex items-center gap-2 mb-3">
            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-bold">G</span>
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">ChatGPT 設定步驟</h3>
        </div>

        {{-- 本地模式 --}}
        <div class="bg-gray-50 dark:bg-gray-700/30 rounded-lg p-4 mb-4">
            <p class="text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">本地模式（Stdio）</p>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">適用於 ChatGPT 桌面版與伺服器在同一台電腦上的情況。</p>
            <p class="text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">1. 開啟 ChatGPT 的 MCP 設定</p>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">在 ChatGPT 桌面版中點擊「設定」→「MCP 伺服器」→「新增伺服器」</p>
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

        {{-- 遠端 HTTP 模式 --}}
        <div class="bg-gray-50 dark:bg-gray-700/30 rounded-lg p-4">
            <p class="text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">遠端 HTTP 模式</p>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">適用於 ChatGPT 桌面版與伺服器在不同電腦上，或使用遠端伺服器的情況。</p>
            <p class="text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">1. 開啟 ChatGPT 桌面版的 MCP 設定</p>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">在 ChatGPT 桌面版中點擊「設定」→「連接的應用程式」（或「MCP 伺服器」，以實際版本為準）→「新增 MCP 伺服器」</p>
            <p class="text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">2. 選擇連線類型</p>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">選擇「遠端」（HTTP）類型</p>
            <p class="text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">3. 填入以下資訊</p>
            <div class="bg-gray-100 dark:bg-gray-600/30 rounded p-3 text-sm text-gray-700 dark:text-gray-300 mt-2">
                <p><strong>名稱：</strong>Threads 管理</p>
                <p><strong>URL：</strong><code>https://你的後台網址/mcp/threads</code></p>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-3">4. 儲存後，ChatGPT 會自動觸發 OAuth 授權流程（詳見上方「HTTP 模式設定步驟」），在瀏覽器中登入並允許即可。</p>
        </div>
    </div>

    {{-- ===== Token 後續管理 ===== --}}
    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
        <div class="flex items-center gap-2 mb-3">
            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-bold">T</span>
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Token 後續管理</h3>
        </div>
        <div class="bg-gray-50 dark:bg-gray-700/30 rounded-lg p-4">
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">每次有新的 AI 客戶端完成 OAuth 授權後，系統會自動建立一筆 token 記錄。你可以在後台左側選單的「<strong>MCP 控管</strong>」頁面查看與管理這些 token：</p>
            <ul class="list-disc list-inside text-sm text-gray-600 dark:text-gray-400 space-y-1 ml-2">
                <li>查看每個 token 的來源（Client 名稱）、授權範圍（<code class="bg-gray-200 dark:bg-gray-600 px-1 rounded text-xs">mcp:use</code>）、建立時間與到期時間</li>
                <li>若某個 token 不再需要使用，可點擊「註銷」將其立即失效</li>
                <li>註銷後該 AI 客戶端將無法再存取 MCP 服務，需重新授權才能恢復</li>
            </ul>
        </div>
    </div>

    {{-- ===== 注意事項 ===== --}}
    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4 mt-4">
        <p class="text-sm font-semibold text-amber-800 dark:text-amber-200 mb-2">注意事項</p>
        <ul class="list-disc list-inside text-sm text-amber-800 dark:text-amber-200 space-y-1">
            <li><strong>對外網址必須可連：</strong>HTTP 模式需要遠端 AI 客戶端能連到你的後台網址。若使用 Cloudflare Tunnel 或 ngrok，請確保 tunnel 正常運作。</li>
            <li><strong>授權範圍固定為 <code class="bg-amber-100 dark:bg-amber-800 px-1 rounded text-xs">mcp:use</code>：</strong>所有 MCP 連線共用此授權範圍，無需手動設定。</li>
            <li><strong>桌面版不受 custom_scheme 限制：</strong>Claude Desktop 與 ChatGPT 桌面版使用瀏覽器進行 OAuth 回呼，不受自訂 URL scheme 限制。</li>
            <li><strong>選單名稱以實際版本為準：</strong>ChatGPT 桌面版的 MCP 設定入口可能因版本而異（如「連接的應用程式」或「MCP 伺服器」），請依實際 App 版本操作。</li>
        </ul>
    </div>
</div>
