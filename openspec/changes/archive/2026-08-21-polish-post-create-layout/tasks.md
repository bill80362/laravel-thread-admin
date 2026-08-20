## 1. 表單排版調整

- [x] 1.1 在 `PostForm.php` 匯入 `Grid`，以 `Grid::make(['lg' => 2])` 建立左右兩欄外層容器（桌面兩欄）
- [x] 1.2 左欄為圖片 Repeater（`columnSpan(['lg' => 1]`，40%），右欄為表單容器（`columnSpan(['lg' => 2]`，60%）
- [x] 1.3 右欄內三欄位（目標帳號、排程時間、貼文內容）以 `columnSpanFull()` 垂直堆疊
- [x] 1.4 以 `columnOrder(['default' => 1])` 設定手機（`<lg`）單欄時圖片優先、其餘欄位依序在後
- [x] 1.5 維持 Filament 預設內容寬度，不設定滿版
- [x] 1.6 將「貼文狀態資訊」Section 置於根層級最上方、佔整列（僅編輯時顯示），下方才為左圖右表佈局

## 2. 目標帳號預設值

- [x] 2.1 在 `PostForm` 的目標帳號 `Select` 上以 `->default()` 帶入目前使用者的第一個 Threads 帳號 ID
- [x] 2.2 確認無任何帳號時預設值為 `null`（欄位留空、不報錯）

## 3. 應用程式語言改為繁中

- [x] 3.1 將 `.env` 的 `APP_LOCALE` 與 `APP_FALLBACK_LOCALE` 改為 `zh_TW`
- [x] 3.2 同步更新 `.env.example`

## 4. 驗證

- [x] 4.1 執行 `php artisan test` 確認既有測試不受影響
- [x] 4.2 於瀏覽器視窗與窄螢幕驗證：`lg` 以上兩欄、`lg` 以下單欄且圖片優先、目標帳號預設第一個
- [x] 4.3 確認登入頁及後台按鈕文字已變為繁體中文
