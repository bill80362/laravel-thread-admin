## Purpose

提供一個公開、不需登入的隱私政策頁面，供 Meta App Review 審核使用，說明服務收集、使用、分享與保存使用者資訊的方式。

## Requirements

### Requirement: 公開隱私政策路由

系統 SHALL 提供路徑為 `/privacy-policy` 的公開 GET 路由，任何使用者不需登入即可存取頁面內容。

#### Scenario: 不需登入即可存取

- **WHEN** 訪客（未登入）以 GET 請求 `/privacy-policy`
- **THEN** 系統回傳 HTTP 200 且顯示完整的隱私政策頁面，不會要求登入

#### Scenario: 直接渲染內容

- **WHEN** Meta 審核員或其他訪客直接開啟隱私政策網址
- **THEN** 頁面直接呈現政策內容，不觸發登入跳轉、403 或 404 錯誤

### Requirement: 隱私政策內容

隱私政策頁面 SHALL 涵蓋以下小節，以繁體中文撰寫：

- 我們收集哪些資訊
- 我們如何使用這些資訊
- 資訊分享與對外揭露
- 資料保存
- 你的權利
- 聯絡方式

#### Scenario: 呈現政策小節

- **WHEN** 訪客檢視 `/privacy-policy` 頁面
- **THEN** 頁面依序呈現上述各小節的說明內容

#### Scenario: 提供聯絡方式

- **WHEN** 訪客檢視頁面的聯絡方式小節
- **THEN** 頁面提供可聯繫的電話號碼（0987-653382）與品牌資訊（OO-Pilot）

### Requirement: 根目錄頁提供入口連結

根目錄介紹頁 footer SHALL 提供指向 `/privacy-policy` 的「隱私政策」連結。

#### Scenario: 從根目錄進入隱私政策

- **WHEN** 訪客開啟根目錄介紹頁並點擊 footer 的「隱私政策」連結
- **THEN** 瀏覽器導向 `/privacy-policy` 並顯示隱私政策頁面
