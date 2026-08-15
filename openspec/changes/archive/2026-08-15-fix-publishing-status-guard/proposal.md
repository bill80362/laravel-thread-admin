## Why

排程發文的兩階段流程中，`PublishScheduledPost` Job 的 `handle()` guard clause 只允許 `status === 'scheduled'` 時繼續執行。

- **第一階段**（`creationId = null`）：貼文 status 為 `scheduled` → 通過 guard，呼叫 `createTextContainer()` 建立 media container，取得 `creation_id`，並將 status 更新為 `publishing`，再 dispatch 第二階段（帶 `creationId`）延遲 30 秒。
- **第二階段**（`creationId = "xxx"`）：貼文 status 已變為 `publishing` → **被 guard 擋下，直接 `return`**，`publishContainer()` 永遠不會被呼叫。

結果是貼文永久卡在 `publishing` 狀態（資料庫中的 Post#1 即為此狀況，`status = 'publishing'`、`updated_at` 停留在第一階段執行完的時間點），發文永遠不會真正發佈到 Threads。

## What Changes

- `PublishScheduledPost::handle()`：將 guard clause 改為依 `creationId` 區分兩階段的預期 status——第一階段要求 `scheduled`，第二階段要求 `publishing`。

## Capabilities

### New Capabilities
<!-- 無新能力，此為純 bug 修復 -->

### Modified Capabilities
<!-- 無現有能力變更 -->

## Impact

- `app/Jobs/PublishScheduledPost.php` — 修正 guard clause 狀態判斷
- `tests/Feature/PublishScheduledPostTest.php` — 新增第二階段（`publishing` 狀態）的測試案例
