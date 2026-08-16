## 1. 資料模型與列舉

- [ ] 1.1 擴充 `App\Enums\ReplyStatus`，新增 `Publishing`（發佈中）與 `Failed`（發佈失敗）案例，並補齊 `getLabel()`／`getColor()`（若有）
- [ ] 1.2 新增 migration：`replies` 表新增 `error_message`（nullable text）與 `publish_attempts`（unsigned integer，預設 0）
- [ ] 1.3 同一 migration 中刪除既有 `source=manual` 且 `threads_reply_id IS NULL` 的歷史記錄
- [ ] 1.3 更新 `App\Models\Reply` 的 `$fillable` 與 `casts()`，納入新欄位
- [ ] 1.4 更新 `ReplyFactory`，補上新欄位的預設值與必要的 states

## 2. Service 層發佈邏輯

- [ ] 2.1 在 `ReplyService` 新增「建立貼文回覆」方法（簽章含 `threads_account_id`、`post_id`、`text`，`source=manual`、`status=new`、`threads_reply_id=null`）
- [ ] 2.2 在 `ReplyService` 新增發佈目標推導邏輯（`threads_reply_id` 非空 → 回覆留言；為空 → 回覆貼文 `threads_media_id`）
- [ ] 2.3 保留／調整既有 `create()` 以符合新語義（移除 `author_username`、`post_id` 必填）
- [ ] 2.4 確保 `ReplyService` 不直接持有 `ThreadsClient` 的同步呼叫，改由 Job 觸發發佈（或依 D3 以 Job 為主）

## 3. 回覆發佈 Job

- [ ] 3.1 新增 `App\Jobs\PublishReply`（仿 `PublishScheduledPost`），兩階段：`createTextContainer` → `publishContainer`
- [ ] 3.2 在 Job 中處理 `ThreadsApiException`：token 失效 → 帳號 `needs_reauth` + 回覆 `failed`；限流 → `failed`；可重試 → 退避重試；否則 `failed`
- [ ] 3.3 發佈成功後更新回覆 `status=replied`、`replied_at=now()`、清空 `error_message`
- [ ] 3.4 設定重試常數（`MAX_PUBLISH_ATTEMPTS`、`RETRY_BACKOFF_SECONDS`）；延遲常數直接引用 `PublishScheduledPost::PUBLISH_DELAY_SECONDS`

## 4. 後台介面調整

- [ ] 4.1 `ReplyForm`：移除「留言者」欄位，`post_id` 改為必填，欄位標籤對齊「新增貼文回覆」
- [ ] 4.2 `CreateReply` 頁面：調整 `mutateFormDataBeforeCreate` 以符合新語義（移除 `author_username`）
- [ ] 4.3 `RepliesTable`：新增按鈕改名「新增貼文回覆」、列表動作「回覆」改名「回應回覆」
- [ ] 4.4 `RepliesTable` 的「回應回覆」action 改為呼叫 `ReplyService`（收斂邏輯），而非直接呼叫 `ThreadsClient`
- [ ] 4.5 列表狀態欄位補齊 `publishing`／`failed` 的顯示（badge 顏色與文字）
- [ ] 4.6 更新 `RepliesSyncNotice` 的 Blade view，加入「回覆發佈約 30 秒後顯示」說明，秒數取自共用常數

## 5. MCP 工具對齊

- [ ] 5.1 `CreateReplyTool`：描述與名稱對齊「建立貼文回覆」，移除 `author_username` 參數，`post_id` 改為必填
- [ ] 5.2 `CreateReplyTool::handle` 改為呼叫 `ReplyService` 的建立貼文回覆方法並觸發發佈
- [ ] 5.3 `CreateReplyTool::schema` 同步移除 `author_username`、`post_id` 標必填
- [ ] 5.4 `ListRepliesTool` 回傳內容納入新狀態與錯誤訊息（若規格要求）

## 6. 使用說明與文件同步

- [ ] 6.1 檢查 `app/Filament/Pages/UsageGuide.php` 對應 view，同步「回覆」相關名詞與流程（如有提及）
- [ ] 6.2 更新 `ThreadsMcpServer` 的 `Instructions` 描述（如回覆語義改變）

## 7. 測試

- [ ] 7.1 更新／新增 `ReplyService` 單元測試（建立貼文回覆、發佈目標推導）
- [ ] 7.2 新增 `PublishReply` job 測試（成功、失敗、重試、token 失效、限流）
- [ ] 7.3 更新回覆後台 Feature 測試（新增表單欄位、按鈕名詞、發佈流程）
- [ ] 7.4 更新 MCP 工具測試（`create-reply` 參數與發佈行為）
- [ ] 7.5 執行相關測試並確保通過

## 8. 收尾

- [ ] 8.1 執行 `vendor/bin/pint --dirty --format agent`
- [ ] 8.2 確認無既有測試被移除，並提供建議 commit 訊息與變更檔案清單
