## Context

專案使用 Filament 5.7.6（Laravel 13 / PHP 8.4）。現有回覆面板位於 `app/Filament/Resources/Replies/`，列表頁 `ListReplies` 目前僅有 `CreateAction` header action，無任何說明提示。

現有慣例：
- Widget 以獨立類別放在 `app/Filament/Widgets/`（如 `ThreadsOverview`），透過 `AdminPanelProvider` 的 `->widgets()` 註冊
- 各 Resource 目前皆未設定 `$navigationSort`、`$navigationGroup`
- 導覽選單項目：Dashboard（Page）、Threads App、Threads 帳號、排程發文、回覆面板

回覆同步機制：`CollectThreadsReplies` Job 透過 scheduler（每分鐘）觸發，對每個 Active 帳號每 5 分鐘輪詢一次（`SYNC_INTERVAL_MINUTES = 5`）。

## Goals / Non-Goals

**Goals:**
- 在回覆列表頁加入明顯的同步說明提示
- 修正複數標籤為「回覆」
- 依指定順序排列導覽選單

**Non-Goals:**
- 不改動回覆抓取/輪詢機制本身（仍為每 5 分鐘同步）
- 不新增「手動立即同步」按鈕
- 不調整其他資源的標籤或排序以外行為

## Decisions

### 1. 同步說明用 Header Widget 呈現（方案 A）

在 `ListReplies` 透過 `getHeaderWidgets()` 掛載一個自訂 Widget，而非用 `$subheading` 或 render hook。

- **理由**：符合 Filament 慣例、可複用、視覺上以卡片形式明顯呈現；且 Widget 有獨立的 Blade view 可完全控制文案與樣式。
- **替代方案**：
  - `$subheading`：僅能顯示純文字於標題下方，樣式弱、不夠顯眼，且不易加入圖示。
  - Render hook（`PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_BEFORE`）：需全域註冊與 scoping，過度工程。

Widget 使用自訂 Blade view（`resources/views/filament/widgets/replies-sync-notice.blade.php`），以 Filament 的 `filament::` 元件（如 icon、card）渲染提示，避免手刻 CSS。

### 2. 複數標籤修正

在 `ReplyResource` 新增 `protected static ?string $pluralModelLabel = '回覆';`。

- **理由**：Filament 在未提供 `$pluralModelLabel` 時，會以 inflector 對 `$modelLabel`（「回覆」）加 `s` 產生「回覆s」。直接指定即可修正。

### 3. 導覽排序

對四個 Resource 新增 `$navigationSort`（int），數值愈小排序愈前：

| 順序 | 項目 | Resource | navigationSort |
|------|------|----------|----------------|
| 1 | Dashboard | `Dashboard::class`（Page） | 0（內建，不動） |
| 2 | APP | `ThreadsAppResource` | 10 |
| 3 | 帳號 | `ThreadsAccountResource` | 20 |
| 4 | 發文 | `PostResource` | 30 |
| 5 | 回覆 | `ReplyResource` | 40 |

- **理由**：以 10 為間隔，保留日後插入新項目的空間。Dashboard 是 Page，Filament 預設排在 Resource 之前，不需調整。

## Risks / Trade-offs

- [Widget 文案與實際同步間隔不一致] → 文案寫死「5 分鐘」，若未來 `SYNC_INTERVAL_MINUTES` 調整，需同步更新文案。可在 Widget 中讀取 `CollectThreadsReplies::SYNC_INTERVAL_MINUTES` 常數動態帶入，避免硬編碼。
- [自訂 Blade view 需符合 Filament 樣式] → 使用 Filament 既有 view component（`filament::` 前綴），避免自訂 CSS 與主題脫節。
- [導覽排序數值衝突] → 目前無任何 `$navigationSort`，使用 10/20/30/40 不會衝突；若未來新增資源需留意避免重複數值。
