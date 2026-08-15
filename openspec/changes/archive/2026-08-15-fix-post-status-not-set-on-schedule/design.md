## Context

`Post` 資料表的 `status` 欄位預設值為 `draft`。`PostForm` 沒有包含 `status` 欄位，`CreatePost` 和 `EditPost` 也沒有在儲存時設定 `status`。使用者填寫 `scheduled_at` 後貼文仍為 `draft`，無法被排程系統拾取。

## Goals / Non-Goals

**Goals:**
- 建立/編輯貼文時，若有設定 `scheduled_at`，自動將 `status` 設為 `scheduled`

**Non-Goals:**
- 不更動 `PostForm` 的欄位配置
- 不更動 `RunThreadsScheduler` 的查詢邏輯

## Decisions

**決策：在 Filament Page 層透過 `mutateFormDataBeforeCreate()` / `mutateFormDataBeforeSave()` 設定 status**

- 理由：這是最小的變更點，不影響表單 UI，只在儲存前注入邏輯
- 邏輯：`scheduled_at` 有值 → `status = 'scheduled'`；無值 → 保持資料庫預設 `draft`

## Risks / Trade-offs

- 低風險：僅在儲存 pipeline 中新增一行賦值
