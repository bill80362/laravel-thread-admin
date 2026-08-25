## Context

回覆抽屜使用 Livewire 的 `wire:submit` 處理表單送出，但目前按鈕和 textarea 在請求期間沒有任何狀態變化。當伺服器回應慢時，使用者無法感知請求正在進行。

現有視圖 (`post-reply-drawer.blade.php`) 使用純內聯 style，沒有引入 Tailwind 或第三方 UI 框架。改動需維持一致的風格。

## Goals / Non-Goals

**Goals:**
- 送出請求期間，按鈕顯示 loading 狀態（disabled + spinner + 文字變更）
- 送出請求期間，textarea 設為 disabled
- 防止請求期間重複點擊送出
- 使用 Livewire 原生 `wire:loading` 機制，不引入額外 JS 或 Alpine.js

**Non-Goals:**
- 不改變送出邏輯 (`sendReply()` 方法不修改)
- 不改變後端 API 或資料庫
- 不引入新的 CSS framework 或 JavaScript 函式庫
- 不處理 job 執行中的非同步狀態（列表中的「傳送中…」已存在，不修改）

## Decisions

### 使用 `wire:loading` 而非 Alpine.js 管理狀態

- **選擇**：全部使用 Livewire 的 `wire:loading` / `wire:target` 指令
- **理由**：此改動只需反映 Livewire 請求的生命週期，`wire:loading` 在請求發起時立即觸發、完成時自動結束，完全符合需求。不需要 Alpine.js 的額外狀態管理。
- **替代方案**：Alpine.js `x-data="{ sending: false }"` + `x-on:livewire-request-success` — 功能更彈性，但對這個簡單場景來說 over-engineering。

### 使用 `wire:loading.attr="disabled"` 而非 `wire:loading` 切換顯示

- **選擇**：使用 `wire:loading.attr="disabled"` 讓按鈕和 textarea 在請求期間 disabled
- **理由**：disabled 屬性本身就能防止點擊，無需額外邏輯
- **替代方案**：用 `wire:loading.remove` 隱藏按鈕再顯示另一個 loading 按鈕 — 需要複製 DOM 節點，較繁瑣

### spinner 使用純 CSS 而非 SVG 或圖示庫

- **選擇**：使用 CSS 旋轉動畫製作 spinner（`border` + `border-radius: 50%`）
- **理由**：專案目前沒有引入任何圖示庫，純 CSS spinner 零依賴
- **替代方案**：引入 Heroicons 或 Font Awesome — 增加依賴，不符合現有專案風格

## Risks / Trade-offs

- `wire:loading` 繫結到 `sendReply` 方法名稱，如果方法改名需同步更新 Blade 中的 `wire:target`
- 如果將來的 Livewire 升級改變 `wire:loading` 的行為，可能需要調整
- 純 CSS spinner 的樣式不如 SVG 精緻，但以「防止雙重提交」的實用目的而言已足夠
