## Purpose

提供一個公開、不需登入的服務條款頁面，供 Meta App Review 審核與一般使用者參閱，說明使用 OO-Pilot 服務時的條款與限制。

## ADDED Requirements

### Requirement: 公開服務條款路由

系統 SHALL 提供路徑為 `/terms-of-service` 的公開 GET 路由，任何使用者不需登入即可存取頁面內容。

#### Scenario: 不需登入即可存取

- **WHEN** 訪客（未登入）以 GET 請求 `/terms-of-service`
- **THEN** 系統回傳 HTTP 200 且顯示完整的服務條款頁面，不會要求登入

#### Scenario: 直接渲染內容

- **WHEN** 使用者直接開啟服務條款網址
- **THEN** 頁面直接呈現條款內容，不觸發登入跳轉、403 或 404 錯誤

### Requirement: 服務條款內容

服務條款頁面 SHALL 以繁體中文涵蓋以下核心小節：

- 接受條款
- 帳號與服務使用
- 使用者責任
- 付費與訂閱
- 免責聲明與責任限制
- 終止與暫停
- 準據法
- 聯絡方式

#### Scenario: 呈現條款小節

- **WHEN** 訪客檢視 `/terms-of-service` 頁面
- **THEN** 頁面依序呈現上述各小節的說明內容

#### Scenario: 提供聯絡方式

- **WHEN** 訪客檢視頁面的聯絡方式小節
- **THEN** 頁面提供可聯繫的電話號碼（0987-653382）與品牌資訊（OO-Pilot）

### Requirement: 根目錄頁提供入口連結

根目錄介紹頁 footer SHALL 提供指向 `/terms-of-service` 的「服務條款」連結。

#### Scenario: 從根目錄進入服務條款

- **WHEN** 訪客開啟根目錄介紹頁並點擊 footer 的「服務條款」連結
- **THEN** 瀏覽器導向 `/terms-of-service` 並顯示服務條款頁面
