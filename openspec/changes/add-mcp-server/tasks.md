## 1. 前置環境確認

- [ ] 1.1 確認 `php artisan install:api --passport` 已完成、Passport keys 存在、`auth:api` guard 可用
- [ ] 1.2 確認 `routes/ai.php` 會被 Laravel MCP 載入（如有需要則建立該檔）

## 2. 共用 Service 層

- [ ] 2.1 建立 `app/Services/PostService.php`：`create()`、`list()`、`find()`
- [ ] 2.2 建立 `app/Services/ReplyService.php`：`create()`（自動設 `source=manual`、`status=new`）、`list()`

## 3. MCP Server 與工具

- [ ] 3.1 建立 `app/Mcp/Servers/ThreadsMcpServer.php` 並註冊六個 tools
- [ ] 3.2 建立 `ListAccountsTool`：列出帳號，支援 `status` 篩選
- [ ] 3.3 建立 `CreatePostTool`：建立排程貼文（驗證必填欄位、設 `scheduled` 狀態）
- [ ] 3.4 建立 `ListPostsTool`：查詢貼文清單，支援帳號／狀態篩選
- [ ] 3.5 建立 `GetPostTool`：查詢單一貼文，不存在時回傳錯誤
- [ ] 3.6 建立 `ListRepliesTool`：查詢回覆清單，支援帳號／貼文／狀態篩選
- [ ] 3.7 建立 `CreateReplyTool`：建立手動回覆記錄

## 4. 路由與註冊

- [ ] 4.1 於 `routes/ai.php` 註冊 `Mcp::oauthRoutes()`
- [ ] 4.2 於 `routes/ai.php` 以 `Mcp::local('threads', ThreadsMcpServer::class)` 註冊本地伺服器
- [ ] 4.3 於 `routes/ai.php` 以 `Mcp::web('/mcp/threads', ThreadsMcpServer::class)->middleware('auth:api')` 註冊 HTTP 伺服器

## 5. Passport 授權視圖（如需）

- [ ] 5.1 執行 `php artisan vendor:publish --tag=mcp-views`
- [ ] 5.2 於 `AppServiceProvider::boot()` 設定 `Passport::authorizationView` 指向 `mcp.authorize` view

## 6. 測試

- [ ] 6.1 為 `PostService` 與 `ReplyService` 撰寫 PHPUnit 測試
- [ ] 6.2 為每個 MCP tool 撰寫測試（happy path、驗證錯誤、not found）
- [ ] 6.3 執行相關測試並確保通過

## 7. 文件與收斂

- [ ] 7.1 更新 `AGENTS.md`：加入 MCP 開發規範、目錄結構、認證方式、業務邏輯整合約定
- [ ] 7.2 執行 `vendor/bin/pint --dirty --format agent` 修正格式
- [ ] 7.3 執行 `php artisan route:list` 確認 MCP 路由註冊成功
