## Why

目前「建立貼文」頁面（`/user/posts/create`）所有欄位以單一直欄依序排列，圖片 Repeater 在左上方、表單欄位在下方，在桌面／平板大螢幕上會造成大量空白與不便的長捲動；此外介面目前為英文（`APP_LOCALE=en`），不利於繁體中文使用者操作。

## What Changes

- 將「建立貼文」表單改為響應式兩欄／單欄布局：
  - 桌面／平板（`lg` 以上）：左右兩欄，左欄圖片、右欄表單欄位，右欄較寬
  - 手機（`<lg`）：單欄，圖片優先在上、表單欄位在下
- 「目標帳號」在建立貼文時預設帶入目前使用者的第一個帳號
- 將應用程式預設語言改為繁體中文（`APP_LOCALE=zh_TW`），使 Filament 後台介面（登入、按鈕、表格、驗證訊息等）全面改為繁體中文

## Capabilities

### New Capabilities

- `post-create-layout`: 「建立貼文」頁面的響應式表單布局，以及目標帳號預設值行為
- `zh-tw-locale`: 應用程式預設語言改為繁體中文，使 Filament 後台介面（登入、按鈕、表格、驗證訊息等）全面顯示繁體中文

### Modified Capabilities

（無既有 spec 的需求變更）

## Impact

- `app/Filament/Resources/Posts/Schemas/PostForm.php`：調整表單布局（Grid、columnSpan、columnOrder）
- `app/Filament/Resources/Posts/Pages/CreatePost.php`：若需設定目標帳號預設值
- `.env` 與 `.env.example`：`APP_LOCALE=zh_TW`、`APP_FALLBACK_LOCALE=zh_TW`
- 全應用程式 Filament 介面語言變更（由 Filament 內建翻譯提供，無需額外套件）
- 不影響資料庫結構、既有 API 或 MCP
