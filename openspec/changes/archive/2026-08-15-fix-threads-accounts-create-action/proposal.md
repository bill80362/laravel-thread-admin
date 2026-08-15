## Why

`ListThreadsAccounts` 頁面保留了 Filament scaffold 產生的 `CreateAction`，但 Threads 帳號是透過 OAuth 綁定而非手動建立。點擊「新增」按鈕會觸發空白 INSERT，因 `threads_user_id` 有 `NOT NULL` 約束而拋出 `SQLSTATE[23000]` 錯誤。

## What Changes

- 移除 `ListThreadsAccounts::getHeaderActions()` 中的 `CreateAction::make()`，改為空陣列。

## Capabilities

<!-- 純 bug fix，無規格層級行為變更。skip_specs: true -->

## Impact

- **修改檔案**：`app/Filament/Resources/ThreadsAccounts/Pages/ListThreadsAccounts.php`（刪除一行）
