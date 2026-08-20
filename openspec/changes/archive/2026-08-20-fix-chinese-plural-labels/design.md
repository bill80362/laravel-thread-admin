## Context

Filament 的 `Resource` 類別中，`$pluralModelLabel` 預設會自動將 `$modelLabel` 加上 "s" 後綴。這對英文有效（如 `Token` → `Tokens`），但對中文無效，導致 List 頁面標題和麵包屑出現多餘的 "s"。

目前 `ReplyResource` 已手動設定 `$pluralModelLabel = '回覆'` 解決此問題，其他三個 Resource 尚未處理。

## Goals / Non-Goals

**Goals:**
- 為 `ThreadsAccountResource`、`PostResource`、`UserResource` 手動設定 `$pluralModelLabel`，使其與 `$modelLabel` 一致

**Non-Goals:**
- 不修改 Filament 框架本身的行為
- 不建立全域的中文 pluralizer

## Decisions

**決策：在各 Resource 中直接設定 `$pluralModelLabel`**

- 理由：這是最簡單、最直接的做法，與 `ReplyResource` 的現有模式一致
- 替代方案：建立自訂的 pluralizer 或覆寫 `getPluralModelLabel()` 方法 — 過度設計，對三個 Resource 而言不值得

## Risks / Trade-offs

- 無風險。這是純 UI 文字修正，不影響任何業務邏輯或資料。
