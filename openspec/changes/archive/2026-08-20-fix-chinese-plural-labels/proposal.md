## Why

Filament 會自動將 `$modelLabel` 加上 "s" 後綴來產生複數標籤（`$pluralModelLabel`），但這對中文無效，導致 List 頁面標題和麵包屑出現多餘的 "s"（例如「Threads 帳號s」、「貼文s」、「使用者s」）。

## What Changes

- 為 `ThreadsAccountResource`、`PostResource`、`UserResource` 手動設定 `$pluralModelLabel`，使其與 `$modelLabel` 一致（中文無單複數區別）
- `ReplyResource` 已正確設定，無需修改
- `McpTokenResource` 為英文，Laravel pluralizer 可正確處理，無需修改

## Capabilities

### New Capabilities

無（純 UI 修正，無新功能）

### Modified Capabilities

無（無行為變更）

## Impact

- `app/Filament/Resources/ThreadsAccounts/ThreadsAccountResource.php`
- `app/Filament/Resources/Posts/PostResource.php`
- `app/Filament/Resources/Users/UserResource.php`
