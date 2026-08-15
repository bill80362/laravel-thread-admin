## Context

動機見 proposal.md - Why。這是單一檔案、單一行的 bug fix，不涉及架構變更。

## Goals / Non-Goals

**Goals:**
- 移除錯誤的 `CreateAction`，使帳號管理頁面不再暴露「手動新增」入口。

**Non-Goals:**
- 不改動 OAuth 綁定流程（`ThreadsAccountResource` 的 toolbar `bindAccount` 已是正確入口）。
- 不變更資料模型或任何 API 行為。

## Decisions

- **移除 `getHeaderActions()` 中的 `CreateAction`**，改回傳空陣列。
  - 理由：帳號唯一建立途徑是 OAuth 綁定，手動建立會因 `threads_user_id` NOT NULL 約束失敗，且 `ThreadsAccountForm` 本身是空的（無欄位）。
  - 替代方案：保留 `CreateAction` 但補齊表單欄位 → 違反「帳號只透過 OAuth 建立」的產品決策，且會誤導使用者手動輸入 token，予以排除。

## Risks / Trade-offs

- 無明顯風險。移除後僅剩 toolbar 的「綁定 Threads 帳號」按鈕作為建立入口，符合設計意圖。
