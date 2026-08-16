## Purpose

提供一份面向非程式人員的步驟化使用說明，涵蓋 Meta App 申請、帳號綁定、排程發文、回覆收集與 MCP 服務設定，降低營運人員的入門門檻。

## ADDED Requirements

### Requirement: 使用說明頁面可於後台存取
系統 SHALL 在後台左側導覽提供「使用說明」頁面，讓登入使用者得以查看完整操作說明。

#### Scenario: 進入使用說明頁面
- **WHEN** 登入使用者點擊左側導覽的「使用說明」
- **THEN** 系統 SHALL 顯示使用說明頁面，包含 Meta App 申請、帳號綁定、排程發文、回覆收集與 MCP 服務設定等章節

### Requirement: 使用說明涵蓋 Meta App 申請流程
使用說明 SHALL 以非程式人員可理解的方式，說明如何申請 Meta App、取得應用程式編號與應用程式密鑰，以及將要管理的 Threads 帳號加入測試人員。

#### Scenario: 說明申請步驟
- **WHEN** 使用者閱讀「前置準備」章節
- **THEN** 說明 SHALL 提供 Meta for Developers 網站網址
- **AND** 說明 SHALL 逐步說明建立應用程式、選擇 Threads use case、取得應用程式編號與密鑰、新增 Threads 測試人員的步驟

### Requirement: 使用說明涵蓋排程發文與回覆收集機制
使用說明 SHALL 以非程式人員可理解的方式，說明排程發文的狀態流程與觸發頻率，以及回覆收集的偵測範圍與頻率。

#### Scenario: 說明排程發文機制
- **WHEN** 使用者閱讀「排程發文」章節
- **THEN** 說明 SHALL 描述貼文的狀態流程（草稿、排程中、發佈中、已發佈、失敗）
- **AND** 說明 SHALL 說明系統每分鐘檢查到期貼文，以及發佈需先建立容器再等待約 30 秒的兩階段流程

#### Scenario: 說明回覆收集機制
- **WHEN** 使用者閱讀「回覆收集」章節
- **THEN** 說明 SHALL 說明系統僅偵測「本系統發出的貼文」的回覆
- **AND** 說明 SHALL 說明每 5 分鐘自動偵測一次新回覆

### Requirement: 使用說明涵蓋 MCP 服務設定
使用說明 SHALL 說明 MCP 服務的本地與 HTTP 兩種模式，並提供 ChatGPT 與 Claude Desktop 的逐步註冊教學。

#### Scenario: 說明 MCP 兩種模式
- **WHEN** 使用者閱讀「MCP 服務」章節
- **THEN** 說明 SHALL 區分本地模式與 HTTP 模式的差異
- **AND** 說明 SHALL 提供 ChatGPT 與 Claude Desktop 的逐步設定步驟
