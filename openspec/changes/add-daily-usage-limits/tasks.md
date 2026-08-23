## 1. 資料層：Migration + Model

- [ ] 1.1 建立 `activity_logs` 表 migration（無 FK，使用 unsignedBigInteger）
- [ ] 1.2 建立 `ActivityLog` Model（含 `user()`、`threadsAccount()` belongsTo 關聯）
- [ ] 1.3 建立 `ActivityLogFactory`
- [ ] 1.4 在 `ActivityLog` Model 新增 `scopeToday()` 與 `countTodayForUser($userId, $type)` 輔助方法

## 2. Job 修改：寫入 Log + 發送前檢查

- [ ] 2.1 修改 `PublishScheduledPost` stage1：發送前檢查 `activity_logs` 今日發文數是否已達 `user.max_daily_posts`，超額則標記 Failed
- [ ] 2.2 修改 `PublishScheduledPost` stage2：發送成功後寫入 `activity_logs`（type=post）
- [ ] 2.3 修改 `PublishReply` stage1：發送前檢查 `activity_logs` 今日回覆數是否已達 `user.max_daily_replies`，超額則標記 Failed
- [ ] 2.4 修改 `PublishReply` stage2：發送成功後寫入 `activity_logs`（type=reply）

## 3. MCP 工具：軟性警告

- [ ] 3.1 修改 `CreatePostTool`：建立成功後計算今日用量，回傳 `warnings` 陣列（含已發送、排程中今日將發送數量）
- [ ] 3.2 修改 `CreateReplyTool`：建立成功後計算今日用量，回傳 `warnings` 陣列

## 4. User 端：列表頁用量提示條

- [ ] 4.1 修改 `ListPosts` 頁面：在表格上方新增用量提示條元件（今日發文 + 今日回覆用量條）
- [ ] 4.2 用量條顯示：已發送數量、上限、進度條、發文額外顯示「排程中今日將發送」

## 5. Admin 端：用量明細查詢

- [ ] 5.1 修改 `UsersTable`：將 `daily_post_usage` 和 `daily_reply_usage` 欄位改為可點擊
- [ ] 5.2 點擊後開啟 Modal/抽屜，顯示該使用者今日的 `activity_logs` 明細（發送時間、帳號、內容、類型）

## 6. User 端：發送紀錄頁面

- [ ] 6.1 建立 `ActivityLogResource`（User Panel），僅顯示當前使用者的記錄
- [ ] 6.2 列表顯示：發送時間、類型、Threads 帳號、內容
- [ ] 6.3 支援依類型（post/reply）篩選

## 7. 測試

- [ ] 7.1 測試 `PublishScheduledPost`：超額時標記 Failed、不呼叫 API
- [ ] 7.2 測試 `PublishScheduledPost`：成功發送後寫入 activity_log
- [ ] 7.3 測試 `PublishReply`：超額時標記 Failed、不呼叫 API
- [ ] 7.4 測試 `PublishReply`：成功發送後寫入 activity_log
- [ ] 7.5 測試 `ActivityLog` Model scope 與 count 輔助方法
