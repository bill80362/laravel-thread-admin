## Context

參見 proposal.md 的 Why 與 What Changes。目前 User 表已有 `max_daily_posts`、`max_daily_replies` 控管欄位，但沒有任何程式碼實際檢查這些限制。專案約束：migration 不使用外鍵約束，關聯僅在 Model 層透過 Eloquent 實現。

## Goals / Non-Goals

**Goals:**
- 建立 `activity_logs` 表記錄每次發送，刪除不減計數
- 在發送前（Job stage1）檢查當日用量，超額則標記 Failed
- MCP 工具建立時回傳軟性用量警告
- User 列表頁頂部顯示用量提示條
- Admin 與 User 皆可查詢用量明細

**Non-Goals:**
- 不處理歷史 log 清理（未來再說）
- 不阻擋建立排程（僅在發送時檢查）
- 不修改 Threads API 呼叫邏輯

## Decisions

### 1. 使用 `activity_logs` 獨立表而非 Post/Reply 表

- **選擇**：新增獨立 `activity_logs` 表
- **理由**：Post 刪除時會硬刪除，無法依賴 Post 表計算歷史用量。獨立表不受刪除影響
- **替代方案**：對 Post 表做軟刪除 — 但會影響現有查詢邏輯，且 Post 表已有複雜狀態機

### 2. 反標準化 `text` 欄位

- **選擇**：`activity_logs` 額外儲存 `text`（發文/回覆內容）
- **理由**：Post/Reply 刪除後內容消失，但 log 仍應保留發文內容以供查詢
- **替代方案**：不存 text，僅靠 reference_id 關聯 — 但刪除後關聯失效

### 3. 檢查點在 Job stage1（建立 container 前）

- **選擇**：`PublishScheduledPost` stage1 和 `PublishReply` stage1 檢查
- **理由**：此時尚未呼叫 Threads API，阻擋成本最低。計數以實際發送日為準
- **替代方案**：在 Service 層檢查 — 但 Service 不知道「今天」是哪天（排程可跨日）

### 4. 寬鬆限制（不鎖）

- **選擇**：`if (todayCount >= maxDaily)` 才阻擋，不處理邊界競爭
- **理由**：使用者明確要求「超發一兩封還好」，不需要悲觀鎖或交易隔離

### 5. Admin 明細使用 Filament RelationManager + 抽屜

- **選擇**：在 User 的 Edit 頁面透過 RelationManager 顯示 `activity_logs`
- **理由**：Filament 內建 RelationManager 支援表格、篩選、分頁，且可內嵌在 Edit 頁面
- **替代方案**：獨立 Resource 頁面 — 但需要額外導航，使用者體驗較差

### 6. User 端用量明細使用獨立 Resource

- **選擇**：新增 `ActivityLogResource`，掛在 User Panel 下
- **理由**：User 只能看自己的資料，透過 `getEloquentQuery()` 過濾 `user_id = auth()->id()`
- **替代方案**：放在貼文列表頁的抽屜 — 但資料量可能很大，獨立頁面更適合

## Risks / Trade-offs

- **競爭條件**：同一使用者的多個 Job 同時執行時，可能短暫超發 → 使用者已接受此風險
- **text 反標準化**：若未來修改發文內容，log 中的 text 不會同步更新 → 這是預期行為，log 記錄的是「當時發送的內容」
- **reference_id 孤兒**：Post/Reply 刪除後 reference_id 無法關聯 → 但 text 已保留，且 threads_account_id 可查出帳號資訊
