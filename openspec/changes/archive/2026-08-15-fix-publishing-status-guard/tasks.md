## 1. 修正 PublishScheduledPost guard clause

- [x] 1.1 在 `PublishScheduledPost::handle()` 中，將 guard clause 改為依 `$this->creationId` 判斷預期 status（第一階段 `scheduled`、第二階段 `publishing`）

## 2. 更新測試

- [x] 2.1 在 `PublishScheduledPostTest` 新增測試：`creationId` 為 null 且貼文 status 為 `scheduled` 時，`createTextContainer()` 應被呼叫並將 status 更新為 `publishing`
- [x] 2.2 確認既有測試 `test_non_scheduled_post_is_skipped` 仍通過（published 狀態應被略過）

## 3. 驗證

- [x] 3.1 執行 `PublishScheduledPostTest` 確保測試通過
- [x] 3.2 執行 `vendor/bin/pint --dirty --format agent`
