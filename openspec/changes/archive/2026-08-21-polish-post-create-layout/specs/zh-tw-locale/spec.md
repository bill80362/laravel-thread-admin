## Purpose

將應用程式預設語言設為繁體中文，讓 Filament 後台所有介面文字（登入、按鈕、表格、驗證訊息等）預設以繁體中文顯示。

## ADDED Requirements

### Requirement: 應用程式預設語言為繁體中文

應用程式的預設語言（default locale）與後備語言（fallback locale）SHALL 皆設定為繁體中文（`zh_TW`），使 Filament 內建翻譯自動套用繁體中文。

#### Scenario: 開啟任一 Filament 頁面
- **WHEN** 使用者以未特別指定語言的方式瀏覽 Filament 後台（如使用者面板或管理面板）
- **THEN** 介面中的內建文字（登入、按鈕、表格操作、驗證訊息等）以繁體中文顯示
