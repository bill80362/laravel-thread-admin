# mcp-server Specification

## Purpose
提供 MCP 伺服器讓外部 AI agent 以程式化方式讀取帳號、建立排程貼文、查詢貼文與回覆、建立手動回覆記錄，補齊目前僅能透過後台介面操作的缺口。

## Requirements

### Requirement: MCP 伺服器以本地與 HTTP 兩種方式提供
系統 SHALL 提供一個 MCP 伺服器，並同時以本地（Artisan command）與 HTTP 兩種方式註冊，讓 AI agent 得以連線使用。

#### Scenario: 本地方式啟動伺服器
- **WHEN** AI agent 以本地方式啟動 MCP 伺服器
- **THEN** 系統 SHALL 以 Artisan command 形式提供伺服器，無需 HTTP 伺服器

#### Scenario: HTTP 方式存取伺服器
- **WHEN** AI agent 透過 HTTP POST 連線到 MCP 端點
- **THEN** 系統 SHALL 提供可供遠端存取的 MCP 端點
- **AND** 端點 SHALL 受到 Passport OAuth（`auth:api`）保護

### Requirement: 列出可用帳號
系統 SHALL 提供 `list-accounts` 工具，回傳可供發文與回覆的已綁定 Threads 帳號清單。

#### Scenario: 列出帳號
- **WHEN** AI agent 呼叫 `list-accounts`
- **THEN** 系統 SHALL 回傳已綁定帳號清單，包含帳號 ID、使用者名稱、顯示名稱與狀態

#### Scenario: 依狀態篩選帳號
- **WHEN** AI agent 呼叫 `list-accounts` 並指定狀態篩選
- **THEN** 系統 SHALL 僅回傳符合該狀態的帳號

### Requirement: 建立排程貼文
系統 SHALL 提供 `create-post` 工具，依指定帳號、內容與排程時間建立一筆排程貼文。

#### Scenario: 建立排程貼文
- **WHEN** AI agent 提供帳號、貼文內容與排程時間呼叫 `create-post`
- **THEN** 系統 SHALL 建立一筆狀態為「排程中」的貼文
- **AND** 回傳新建貼文的資訊

#### Scenario: 缺少必填欄位
- **WHEN** AI agent 呼叫 `create-post` 但缺少帳號、內容或排程時間
- **THEN** 系統 SHALL 回傳驗證錯誤，且不建立貼文

### Requirement: 查詢貼文清單
系統 SHALL 提供 `list-posts` 工具，回傳貼文清單，並支援依帳號、狀態篩選。

#### Scenario: 列出貼文
- **WHEN** AI agent 呼叫 `list-posts`
- **THEN** 系統 SHALL 回傳貼文清單，包含內容、狀態、排程與發佈時間

#### Scenario: 依帳號與狀態篩選
- **WHEN** AI agent 呼叫 `list-posts` 並指定帳號或狀態
- **THEN** 系統 SHALL 僅回傳符合條件的貼文

### Requirement: 查詢單一貼文
系統 SHALL 提供 `get-post` 工具，依貼文 ID 回傳單一貼文的詳細資訊。

#### Scenario: 查詢存在的貼文
- **WHEN** AI agent 提供有效貼文 ID 呼叫 `get-post`
- **THEN** 系統 SHALL 回傳該貼文的完整資訊

#### Scenario: 查詢不存在的貼文
- **WHEN** AI agent 提供不存在的貼文 ID 呼叫 `get-post`
- **THEN** 系統 SHALL 回傳錯誤，表示貼文不存在

### Requirement: 查詢回覆清單
系統 SHALL 提供 `list-replies` 工具，回傳回覆清單，並支援依帳號、貼文、狀態篩選。

#### Scenario: 列出回覆
- **WHEN** AI agent 呼叫 `list-replies`
- **THEN** 系統 SHALL 回傳回覆清單，包含留言者、內容、狀態與時間

#### Scenario: 依帳號、貼文與狀態篩選
- **WHEN** AI agent 呼叫 `list-replies` 並指定帳號、貼文或狀態
- **THEN** 系統 SHALL 僅回傳符合條件的回覆

### Requirement: 建立手動回覆記錄
系統 SHALL 提供 `create-reply` 工具，建立一筆手動回覆記錄，其行為與介面手動新增一致。

#### Scenario: 建立回覆記錄
- **WHEN** AI agent 提供來源帳號、留言者與留言內容呼叫 `create-reply`
- **THEN** 系統 SHALL 建立一筆回覆記錄
- **AND** `source` 自動設為 `manual`
- **AND** `status` 自動設為 `new`

#### Scenario: 可選欄位留空
- **WHEN** AI agent 未提供所屬貼文呼叫 `create-reply`
- **THEN** 系統 SHALL 建立回覆記錄，`post_id` 為 null

### Requirement: 業務邏輯與介面整合
系統 SHALL 將貼文與回覆的建立／查詢邏輯收斂到共用 Service 層，確保 MCP 工具與後台介面遵循相同業務規則。

#### Scenario: 共用業務邏輯
- **WHEN** MCP 工具或後台介面執行建立貼文、建立回覆、查詢貼文、查詢回覆
- **THEN** 兩者 SHALL 呼叫相同的 Service 方法，遵循相同的驗證與資料寫入規則

### Requirement: 帳號綁定不納入 MCP 範圍
系統 SHALL 不在 MCP 中提供帳號綁定（OAuth 授權）相關操作；綁定僅能於後台介面完成。

#### Scenario: MCP 不包含綁定操作
- **WHEN** AI agent 查詢 MCP 伺服器提供的工具清單
- **THEN** 工具清單 SHALL 不包含任何帳號綁定或 OAuth 授權相關工具

### Requirement: OAuth 授權流程未登入時重導向至後台登入頁
系統 SHALL 在使用者未登入而存取 HTTP 模式 MCP 的 OAuth 授權端點（`/oauth/authorize`）時，將使用者重導向至後台登入頁，而非回傳伺服器錯誤。

#### Scenario: 未登入存取 OAuth 授權端點
- **WHEN** 使用者尚未登入，且存取 OAuth 授權端點以開始授權流程
- **THEN** 系統 SHALL 將使用者重導向至後台登入頁
- **AND** 系統 SHALL 不回傳 `Route [login] not defined` 或其他伺服器錯誤

#### Scenario: 已登入存取 OAuth 授權端點
- **WHEN** 使用者已登入，且存取 OAuth 授權端點
- **THEN** 系統 SHALL 正常顯示授權確認頁，不進行登入重導向

### Requirement: MCP 端點認證失敗時回傳 JSON 錯誤
系統 SHALL 在 HTTP 模式 MCP 端點（`/mcp/*`）的 `auth:api` 認證失敗時，回傳 JSON 格式的 401 錯誤回應，而非 HTML 重導向。

#### Scenario: 未提供有效 token 呼叫 MCP 端點
- **WHEN** AI agent 未提供有效 Bearer token 而呼叫 MCP 端點
- **THEN** 系統 SHALL 回傳 JSON 格式的 401 Unauthenticated 回應
- **AND** 系統 SHALL 不回傳 HTML 重導向頁面
