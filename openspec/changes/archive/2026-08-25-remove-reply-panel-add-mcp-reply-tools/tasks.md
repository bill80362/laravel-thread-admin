## 1. 移除回覆面板

- [x] 1.1 刪除 `app/Filament/Resources/Replies/` 整個目錄（含 `ReplyResource.php`、`Pages/`、`Schemas/`、`Tables/`、`Widgets/`）
- [x] 1.2 刪除 `resources/views/filament/widgets/replies-sync-notice.blade.php`
- [x] 1.3 刪除 `tests/Feature/ReplyResourceTest.php`

## 2. 新增 MCP 工具：ReplyToReplyTool

- [x] 2.1 建立 `app/Mcp/Tools/ReplyToReplyTool.php`：接收 `reply_id` 與 `text`，呼叫 `ReplyService::publish()`，回傳新回覆記錄
- [x] 2.2 在 `ThreadsMcpServer` 的 `$tools` 陣列註冊 `ReplyToReplyTool`

## 3. 新增 MCP 工具：UpdateReplyStatusTool

- [x] 3.1 建立 `app/Mcp/Tools/UpdateReplyStatusTool.php`：接收 `reply_id` 與 `status`（new/read/ignored/replied），更新 Reply 記錄的狀態與對應時間戳
- [x] 3.2 在 `ThreadsMcpServer` 的 `$tools` 陣列註冊 `UpdateReplyStatusTool`

## 4. 更新使用說明

- [x] 4.1 更新 `resources/views/filament/pages/usage-guide/chapter2.blade.php`：移除「回覆面板」相關文字，改為描述貼文抽屜回覆與 MCP 回覆工具

## 5. 清理與驗證

- [x] 5.1 執行 `php artisan config:clear` 確保無殘留設定
- [x] 5.2 執行 `vendor/bin/pint --format agent` 統一程式碼風格
- [x] 5.3 執行 `php artisan test --compact --filter=PostReplyDrawer` 確保抽屜測試仍通過
