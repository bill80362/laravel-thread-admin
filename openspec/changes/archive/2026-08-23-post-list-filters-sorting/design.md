## Context

貼文列表頁面 `ListPosts.php` 目前使用卡片式佈局（`contentGrid`），完全沒有篩選器，也沒有設定預設排序。`PostsTable.php` 中部分欄位有 `->sortable()`，但實際顯示的 `ListPosts.php` 沒有。

資料庫為 SQLite。SQLite 中 `ORDER BY scheduled_at DESC` 會將 NULL 視為最小值而排在最前面，需要額外處理才能達成「NULL 置底」。

## Goals / Non-Goals

**Goals:**
- 在貼文列表提供 6 種篩選器：狀態、帳號、發佈時間範圍、排程時間範圍、內容關鍵字、錯誤訊息關鍵字。
- 支援 4 個欄位排序：發佈時間、排程時間、帳號、狀態。
- 預設以「排程時間反向」排序，且 `scheduled_at` 為 NULL 的貼文置底。

**Non-Goals:**
- 不新增自訂篩選器元件（使用 Filament 內建元件組合）。
- 不修改資料庫結構或既有 API/MCP。
- 不處理其他資源（如回覆）的篩選排序。

## Decisions

### D1: 篩選器使用 Filament 內建元件組合

Filament 內建篩選器僅有 `Filter`、`SelectFilter`、`MultiSelectFilter`、`TernaryFilter`、`TrashedFilter`、`QueryBuilder`，**沒有內建的 `DateRangeFilter` 或 `TextFilter`**。因此：

- **狀態**：`SelectFilter::make('status')->options(PostStatus::class)` — 直接使用 enum。
- **帳號**：`SelectFilter::make('threads_account_id')->relationship('threadsAccount', 'username')` — 使用 relationship 選項，並限定目前使用者的帳號。
- **發佈時間 / 排程時間範圍**：使用 `Filter::make('published_at_range')->query(...)` 搭配 `Filament\Forms\Components\DateRangePicker`（schema 元件）自訂日期範圍篩選器。
- **內容 / 錯誤訊息關鍵字**：使用 `Filter::make('text_search')->query(...)` 搭配 `TextInput` 自訂文字篩選器。

> 替代方案：使用 `QueryBuilder` 一次提供所有欄位的進階篩選。但使用者明確要求「各種欄位的篩選器」以直覺方式呈現，故採個別篩選器組合，而非 QueryBuilder。

### D2: 預設排序「排程時間反向、NULL 置底」

`->defaultSort('scheduled_at', 'desc')` 只能處理單一欄位排序，無法表達「NULL 置底」。因此改用 `defaultSort()` 傳入 **query closure**：

```php
->defaultSort(function (Builder $query): Builder {
    return $query
        ->orderByRaw('scheduled_at IS NULL')  // NULL 置底
        ->orderBy('scheduled_at', 'desc');
})
```

> 替代方案：覆寫 `getEloquentQuery()`。但 `defaultSort()` 的 query closure 更精準，只在「未指定其他排序」時套用，且不影響使用者手動排序。

### D3: 排序欄位設定

在 `ListPosts.php` 的 `table()` 中，為以下欄位加上 `->sortable()`：
- `published_at`（發佈時間）
- `scheduled_at`（排程時間）
- `threadsAccount.username`（帳號）
- `status`（狀態）

> 注意：`PostsTable.php` 是另一份表格設定（可能用於其他用途），本次以 `ListPosts.php` 為主。若 `PostsTable.php` 也需同步，則一併加上。

## Risks / Trade-offs

- **SQLite NULL 排序行為** → 已透過 `orderByRaw('scheduled_at IS NULL')` 明確處理，與資料庫引擎無關。
- **自訂篩選器需手動寫 query** → 需確認 `DateRange` 與 `TextInput` 的 state 格式，實作時以 Filament 文件為準。
- **帳號篩選器需限定使用者** → 透過 `modifyQueryUsing` 限定 `user_id = auth()->id()`，避免看到他人帳號。
