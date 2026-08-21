## MODIFIED Requirements

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

#### Scenario: 建立貼文時含空圖片 item
- **WHEN** 使用者在後台建立貼文，新增了圖片 item 但未上傳任何檔案（`image_path` 為空）
- **THEN** 系統 SHALL 跳過該空圖片 item，不在 `post_images` 表中建立記錄
- **AND** 系統 SHALL 正常建立貼文，不因空圖片 item 而失敗

#### Scenario: 編輯貼文時含空圖片 item
- **WHEN** 使用者在後台編輯貼文，表單中存在 `image_path` 為空的圖片 item
- **THEN** 系統 SHALL 跳過該空圖片 item，不寫入 `post_images` 表

#### Scenario: 非排程中狀態不可編輯圖片與內文
- **WHEN** 使用者編輯狀態非 `Draft` 或 `Scheduled` 的貼文（如已發佈、刪除中）
- **THEN** 系統 SHALL 停用圖片上傳與內文編輯欄位
- **AND** 系統 SHALL 拒絕儲存該貼文的任何變更
