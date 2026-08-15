## Context

貼文的編輯頁面由 `EditPost`（`Filament\Resources\Pages\EditRecord`）驅動，表單 schema 定義在 `PostForm::configure()`，目前僅含三個可編輯欄位：`threads_account_id`、`text`、`scheduled_at`。

`status`、`published_at`、`error_message` 三個欄位由系統寫入：
- `status`：`CreatePost`/`EditPost` 的 `mutateFormDataBeforeSave` 設為 `scheduled`；`PublishScheduledPost` Job 依發佈流程更新為 `publishing` → `published`/`failed`。
- `published_at`：發佈成功時寫入。
- `error_message`：發佈失敗時寫入。

這些欄位皆屬唯讀性質，不應在表單中提供編輯。

Filament v5 中，`Placeholder`（`Filament\Forms\Components\Placeholder`）已被標記為 deprecated，其實質是 `TextEntry` 的別名。唯讀顯示應改用 `TextEntry` 搭配 `->state()`。

## Goals / Non-Goals

**Goals:**
- 在編輯頁面顯示唯讀的狀態、發佈時間、錯誤訊息。
- 將狀態標籤與顏色的對應邏輯集中到 `PostStatus` enum，供列表與編輯頁共用。
- 不影響建立頁面與現有欄位行為。

**Non-Goals:**
- 不開放 `status`、`published_at`、`error_message` 的手動編輯。
- 不改變編輯按鈕的可見性規則（維持僅 Draft/Scheduled 可編輯）。
- 不新增 View 頁面。

## Decisions

### Decision 1: 在 PostForm 最上方新增唯讀 Section
- **做法**：`PostForm::configure()` 的 `components()` 陣列最前面插入一個 `Section::make('貼文狀態資訊')`，內含三個唯讀元件，並設 `->hiddenOn('create')`。
- **理由**：直接放在表單 schema 最上方，使用者進編輯頁第一眼即可看到狀態；`hiddenOn('create')` 避免建立頁面顯示無意義的空白狀態。
- **替代方案**：覆寫 `EditPost::content()` 自訂頁面結構。缺點是需額外處理 `getFormContentComponent()` 等，較繁瑣且破壞現有預設結構。

### Decision 2: 唯讀欄位使用 TextEntry（非 deprecated 的 Placeholder）
- **做法**：使用 `Filament\Infolists\Components\TextEntry`（而非 `Placeholder`），以 `->state()` 提供顯示值。
- **理由**：`Placeholder` 已被標記 deprecated，且實質是 `TextEntry` 的包裝；直接使用 `TextEntry` 更符合 v5 語意。
- **替代方案**：使用 `Filament\Schemas\Components\Text` prime component。缺點是缺乏 label-value 的對齊排版，需手動處理。

### Decision 3: PostStatus enum 實作 HasLabel 與 HasColor
- **做法**：讓 `PostStatus` 實作 `Filament\Support\Contracts\HasLabel` 與 `HasColor`，將中文標籤（草稿/排程中/發佈中/已發佈/失敗）與顏色（gray/warning/info/success/danger）移至 enum。
- **理由**：`TextEntry`/`TextColumn` 遇到 cast 為 enum 且實作 `HasLabel`/`HasColor` 的欄位時，會自動套用標籤與顏色，無需每處重複寫 `formatStateUsing`/`color` 閉包。
- **替代方案**：保留 `PostsTable` 的閉包寫法，僅在編輯頁另寫一份。缺點是邏輯重複、易不同步。

### Decision 4: 狀態用 badge 呈現
- **做法**：狀態 `TextEntry` 使用 `->badge()`，顏色由 enum `HasColor` 提供；發佈時間用 `->dateTime('Y-m-d H:i')` 與 `->placeholder('-')`；錯誤訊息用 `->placeholder('-')` 並在 `failed` 時以 danger 色呈現。
- **理由**：與列表頁視覺一致，提升狀態可讀性。

## Risks / Trade-offs

- **[狀態顯示與編輯權限的矛盾]** → `published_at`、`error_message` 在 Draft/Scheduled 狀態下為空，Section 會顯示佔位符。這是可接受的，因 status 本身即有資訊價值，且未來開放 failed 編輯後此區塊立即有意義。
- **[enum 實作 HasLabel/HasColor 影響列表頁]** → 列表頁現有閉包邏輯需同步移除，否則可能衝突。需一併更新 `PostsTable` 並確認視覺不變。
- **[TextEntry 在 form schema 中的相容性]** → 需確認 Filament v5 允許在 form schema 中直接使用 `TextEntry`（`Placeholder` 既為其別名，理論上相容）。若不相容，退回使用 `Placeholder`。
