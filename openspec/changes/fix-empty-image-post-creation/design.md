## Context

見 proposal.md - Why。問題根源：Filament 的 `Repeater->relationship()` 會自動設定 `dehydrated(false)`，導致 `images` 不會出現在 `CreateRecord::create()` 的 `getState()` 回傳資料中。因此 `CreatePost::mutateFormDataBeforeCreate()` 中過濾空圖片的邏輯永遠不會執行，空的 `image_path` 仍被寫入 `post_images`，違反 NOT NULL 約束。

## Goals / Non-Goals

**Goals:**
- 建立/編輯貼文時，空的圖片 item（未上傳檔案）不得寫入 `post_images`。
- 保留既有編輯限制：非 `Draft` / `Scheduled` 狀態的貼文，圖片與內文皆不可編輯。

**Non-Goals:**
- 不變更 `PostService::create()` 的圖片處理邏輯（MCP 路徑不受影響，因為 MCP 傳入的是 `image_paths` / `image_urls` 陣列，非 Repeater）。
- 不變更資料庫結構。

## Decisions

### 使用 Filament 官方鉤子過濾空圖片 item

在 `PostForm` 的圖片 `Repeater` 上使用 `mutateRelationshipDataBeforeCreateUsing()` 與 `mutateRelationshipDataBeforeSaveUsing()`。當 item 的 `image_path` 為空時回傳 `null`，Filament 的 `saveToRelationship()` 會跳過該 item（`if ($itemData === null) { continue; }`）。

**理由：** 這是 Filament 提供的官方鉤子，直接作用於 Repeater 的 relationship 儲存流程，能正確攔截空的 item。相較於在 `mutateFormDataBeforeCreate()` 過濾（因 `dehydrated(false)` 而無效），此方式才是真正生效的位置。

**替代方案考量：**
- 在 `mutateFormDataBeforeCreate()` 過濾：已證實無效（`$data['images']` 永遠為空）。
- 在 `PostImage` model 的 `saving` event 攔截：過於底層，且無法乾淨地「跳過」item（需拋例外或回傳 false，會中斷整個儲存流程）。

### 移除無效的過濾邏輯

移除 `CreatePost::mutateFormDataBeforeCreate()` 與 `EditPost::mutateFormDataBeforeSave()` 中針對 `$data['images']` 的過濾程式碼，因為該邏輯從未生效，保留只會誤導後續維護者。

### 編輯限制維持不變

`PostForm` 中各欄位（圖片 Repeater、內文、帳號、排程時間）的 `disabled` 條件已正確限制非 `Draft` / `Scheduled` 狀態不可編輯，本次不變更。`EditPost::mutateFormDataBeforeSave()` 中的狀態檢查（拋 `ValidationException`）也保留。

## Risks / Trade-offs

- [`mutateRelationshipDataBeforeSaveUsing` 回傳 `null` 只跳過更新，不處理刪除] → 刪除圖片由 Repeater 的 `saveToRelationship()` 自動比對 existing records 處理，無需額外處理。
- [空 item 判斷依賴 `image_path` 欄位] → 目前 Repeater 內僅有 `image_path` 一個欄位，判斷基準明確。
