## Why

回覆抽屜的「送出」按鈕在請求期間沒有任何 UI 反饋，當伺服器回應較慢時，使用者會誤以為沒按到而重複點擊，導致同一篇回覆被送出多次。

## What Changes

- 在 Livewire 請求進行期間，送出按鈕顯示 loading 狀態（disabled + spinner 圖示 + 文字變更為「送出中…」）
- 在請求期間，textarea 設為 disabled 防止編輯
- 防止雙重提交（double-submit），按鈕在請求期間無法再次點擊

## Capabilities

### New Capabilities
- `reply-drawer/send-loading`: 回覆抽屜送出按鈕的 loading 狀態與雙重提交防護

### Modified Capabilities
- （無，行為不變，僅 UI 互動增強）

## Impact

- `resources/views/livewire/post-reply-drawer.blade.php` — 修改表單、按鈕、textarea 的 HTML
- 僅 Blade 視圖層級改動，不影響 PHP 邏輯、不影響 API、不影響資料庫
