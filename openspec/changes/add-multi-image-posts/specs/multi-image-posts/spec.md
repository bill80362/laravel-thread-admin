## Purpose

支援單篇貼文夾帶多張圖片（2-10 張），透過 Threads Carousel API 發佈為輪播貼文，並在後台與 MCP 提供多圖上傳、排序與管理功能。

## ADDED Requirements

### Requirement: 多圖片儲存
系統 SHALL 以獨立資料表 `post_images` 儲存貼文的多張圖片，每張圖片包含路徑與排序欄位，並與 `Post` 建立一對多關聯。

#### Scenario: 建立含多張圖片的貼文
- **WHEN** 使用者建立貼文並上傳 3 張圖片
- **THEN** 系統 SHALL 在 `post_images` 表中建立 3 筆記錄，`sort_order` 依上傳順序遞增（0, 1, 2）
- **AND** 每筆記錄 SHALL 關聯至該貼文

#### Scenario: 純文字貼文無圖片記錄
- **WHEN** 使用者建立純文字貼文（無圖片）
- **THEN** 系統 SHALL 不在 `post_images` 表中建立任何記錄

### Requirement: 圖片數量限制
系統 SHALL 限制每篇貼文的圖片數量為 0 到 10 張。

#### Scenario: 上傳超過 10 張圖片
- **WHEN** 使用者嘗試上傳超過 10 張圖片
- **THEN** 系統 SHALL 回傳驗證錯誤，提示圖片數量上限為 10 張

#### Scenario: 上傳 1 張圖片
- **WHEN** 使用者上傳 1 張圖片
- **THEN** 系統 SHALL 接受並走單圖發佈流程（非 Carousel，因 Carousel 最少需 2 張）

### Requirement: 圖片排序
系統 SHALL 支援圖片排序，使用者可調整圖片在輪播中的顯示順序。

#### Scenario: 調整圖片順序
- **WHEN** 使用者在後台調整圖片排序（如拖曳或修改 sort_order）
- **THEN** 系統 SHALL 依新的 `sort_order` 儲存並在發佈時依序排列

### Requirement: Carousel API 發佈
系統 SHALL 在圖片數量為 2-10 張時，使用 Threads Carousel API 三步驟流程發佈貼文。

#### Scenario: 發佈 3 張圖片的 Carousel 貼文
- **WHEN** 排程時間到達，貼文包含 3 張圖片
- **THEN** 系統 SHALL 依序為每張圖片建立 `is_carousel_item=true` 的 media container
- **AND** 系統 SHALL 建立 `media_type=CAROUSEL` 的 carousel container，`children` 參數包含所有子 container ID
- **AND** 系統 SHALL 等待處理時間後發佈 carousel container
- **AND** 發佈成功後 SHALL 更新貼文狀態為「已發佈」

#### Scenario: 發佈 1 張圖片的貼文
- **WHEN** 排程時間到達，貼文僅包含 1 張圖片
- **THEN** 系統 SHALL 使用既有單圖發佈流程（`createImageContainer` + `publishContainer`），不走 Carousel 流程

#### Scenario: Carousel 部分圖片建立失敗
- **WHEN** 發佈 Carousel 貼文時，其中一張圖片的 media container 建立失敗
- **THEN** 系統 SHALL 依現有重試機制處理（最多重試 3 次）
- **AND** 若最終仍失敗，SHALL 將貼文狀態標記為「失敗」並記錄錯誤訊息

### Requirement: 後台多圖上傳與排序
系統 SHALL 在 Filament 後台貼文表單中提供多圖上傳與排序功能。

#### Scenario: 上傳多張圖片
- **WHEN** 使用者在後台建立貼文並選擇多個圖片檔案
- **THEN** 系統 SHALL 接受最多 10 個圖片檔案
- **AND** 每個檔案 SHALL 符合 JPEG/PNG 格式、最大 8MB 限制

#### Scenario: 拖曳排序圖片
- **WHEN** 使用者在後台編輯貼文時拖曳圖片調整順序
- **THEN** 系統 SHALL 儲存新的排序並在預覽中反映

#### Scenario: 刪除已上傳圖片
- **WHEN** 使用者在後台編輯貼文時刪除其中一張圖片
- **THEN** 系統 SHALL 移除該圖片記錄與儲存檔案

### Requirement: MCP 多圖 URL 支援
系統 SHALL 在 MCP `create-post` 工具中支援傳入多個圖片 URL。

#### Scenario: 傳入多個圖片 URL
- **WHEN** AI agent 呼叫 `create-post` 並提供 `image_urls` 陣列包含 3 個公開 URL
- **THEN** 系統 SHALL 建立貼文並儲存 3 張圖片記錄，依陣列順序設定 `sort_order`

#### Scenario: 傳入超過 10 個圖片 URL
- **WHEN** AI agent 呼叫 `create-post` 並提供超過 10 個 `image_urls`
- **THEN** 系統 SHALL 回傳驗證錯誤，提示圖片數量上限為 10 張

#### Scenario: 傳入空陣列
- **WHEN** AI agent 呼叫 `create-post` 並提供空的 `image_urls` 陣列
- **THEN** 系統 SHALL 視為純文字貼文（若 `text` 有值）或回傳驗證錯誤（若 `text` 也為空）

### Requirement: 向後相容
系統 SHALL 在 migration 中將既有 `posts.image_path` 資料遷移至 `post_images` 表後，drop `posts.image_path` 欄位。

#### Scenario: 既有單圖貼文遷移
- **WHEN** 執行 migration
- **THEN** 系統 SHALL 將所有 `image_path` 不為 null 的既有貼文，在 `post_images` 表中建立對應記錄（`sort_order = 0`）
- **AND** 系統 SHALL drop `posts.image_path` 欄位
- **AND** 既有貼文的發佈行為 SHALL 保持不變（單圖流程）
