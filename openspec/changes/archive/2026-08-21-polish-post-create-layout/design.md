## Context

「建立貼文」表單定義於 `app/Filament/Resources/Posts/Schemas/PostForm.php`，目前所有欄位以根級別（root level）依序排列於單一直欄。欄位包含：目標帳號 Select、圖片 Repeater、貼文內容 Textarea、排程時間 DateTimePicker。既有欄位已是 Filament 元件，可直接用 `Grid`、`columnSpan()`、`columnOrder()` 重新編排，無需更改資料模型。

語言部分：`.env` 已設 `APP_LOCALE=en`、`APP_FALLBACK_LOCALE=en`。Filament 及其子套件內建 `zh_TW` 翻譯，改 locale 即可全介面繁中，無需額外套件或手動翻譯。

## Goals / Non-Goals

**Goals:**
- 讓「建立貼文」表單在 `lg` 以上寬度為左右兩欄（右欄較寬），`lg` 以下為單欄且圖片優先
- 建立貼文時目標帳號預設為使用者的第一個帳號
- 全 Filament 介面改為繁體中文

**Non-Goals:**
- 不修改編輯頁（EditPost）——僅針對 Create 頁的排版；Edit 沿用現有根層級欄位（可自動繼承，但本次不特別調整）
- 不啟用 `filament-language-switch` 語言切換（本專案只需全繁中）
- 不更動資料庫結構

## Decisions

### D1: 使用 `Grid` + `columnSpan()` + `columnOrder()` 重排表單

- 以 `Filament\Schemas\Components\Grid` 建立兩欄容器，僅在 `lg` 以上生效：`Grid::make(['lg' => 2])`
- 圖片 Repeater 置左欄（`columnSpan(['lg' => 1])`），右欄以巢狀 `Grid::make(['lg' => 2])` 收納目標帳號、排程時間、貼文內容（`columnSpanFull` 或分欄）
- 手機端 `<lg` 預設單欄；用 `columnOrder()` 確保圖片優先
- **替代方案考量**：整個 schema 縮排成單一 `Section` 並用 `columns()`——會破壞既有「貼文狀態資訊」Section 與 `hiddenOn(Create)` 的語意，故採 `Grid` 較乾淨

### D2. 目標帳號預設第一個帳號

- 在 `CreatePost::mutateFormDataBeforeCreate()` 或 `PostForm` 的 `Select->default()` 設定預設值
- 採用在 `PostForm` 的 `Select` 上以 `->default(fn () => ...)` 取得目前使用者第一個帳號 ID
- 當無帳號時回傳 `null`（欄位留空）
- 理由：宣告式、貼近元件，易讀易測

### D3. 語言改為繁中

- `.env`：`APP_LOCALE=zh_TW`、`APP_FALLBACK_LOCALE=zh_TW`
- `.env.example` 同步更新
- 由 Filament 內建翻譯處理，無需額外發布或翻譯檔
- 備註：若使用者切瀏覽器語言而想覆蓋，屬 `filament-language-switch` 功能，本次不做

## Risks / Trade-offs

- **[R] 桌面兩欄若圖片很小、右欄欄位多，視覺可能失衡** → 採「右欄較寬」（左 images 佔 1，右表單區較寬），圖片區最高，右欄集中操作欄位
- **[R] 目標帳號 default 使用 DB 查詢，若有大量帳號略耗資源** → 僅取第一個，輕量查詢，可接受
- **[R] 改 locale 影響全站** → 為預期行為，屬此需求範圍；測試需以 `zh_TW` 執行驗證

## Migration Plan

- 無資料庫變更。改動皆為 Filament 表單宣告與環境檔，部署後立即生效
- 回滾：還原 `PostForm.php` 與 `.env` 即可
