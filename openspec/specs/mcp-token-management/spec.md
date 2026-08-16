# mcp-token-management Specification

## Purpose

提供 MCP OAuth token 的後台管理介面，讓登入使用者查看與註銷自己透過 OAuth 取得的 token，確保 MCP 服務的授權可控。

## Requirements

### Requirement: 列出當前使用者的 MCP token
系統 SHALL 在後台提供「MCP 控管」頁面，列出當前登入使用者透過 OAuth 取得的 MCP token，並顯示 Client 名稱、授權範圍、建立時間、到期時間與撤銷狀態。

#### Scenario: 查看 token 清單
- **WHEN** 登入使用者進入「MCP 控管」頁面
- **THEN** 系統 SHALL 僅列出屬於當前登入使用者的 token
- **AND** 每個 token SHALL 顯示其所屬 Client 名稱、授權範圍、建立時間、到期時間與撤銷狀態

#### Scenario: 無 token 時顯示空清單
- **WHEN** 登入使用者沒有任何 MCP token
- **THEN** 系統 SHALL 顯示空清單，不顯示其他使用者的 token

### Requirement: 註銷 MCP token
系統 SHALL 提供註銷功能，讓登入使用者撤銷自己已授權的 MCP token。

#### Scenario: 註銷 token
- **WHEN** 登入使用者在「MCP 控管」頁面對某一 token 執行註銷動作
- **THEN** 系統 SHALL 將該 token 標記為已撤銷
- **AND** 系統 SHALL 顯示註銷成功的通知

#### Scenario: 僅能操作自己的 token
- **WHEN** 登入使用者嘗試註銷不屬於自己的 token
- **THEN** 系統 SHALL 拒絕該操作
