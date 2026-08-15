## Purpose

定義排程發文在遇到 Threads API 暫時性錯誤時的自動重試行為，避免暫時性失敗導致貼文永久停留在失敗狀態。

## ADDED Requirements

### Requirement: 暫時性錯誤自動重試
系統 SHALL 在發文過程中遇到可重試的暫時性錯誤時，自動以有限次數與退避延遲重試，超過重試上限才將貼文標記為失敗。

#### Scenario: 暫時性錯誤重試成功
- **WHEN** 發文 API 回傳暫時性錯誤（如 HTTP 5xx 或「The requested resource does not exist」）
- **AND** 尚未超過重試次數上限
- **THEN** 系統 SHALL 依退避策略自動重新嘗試發文
- **AND** 若後續重試成功，貼文狀態更新為 `published`

#### Scenario: 暫時性錯誤超過重試上限
- **WHEN** 發文 API 持續回傳暫時性錯誤
- **AND** 重試次數已達上限
- **THEN** 系統 SHALL 將貼文狀態更新為 `failed`，並記錄錯誤訊息

#### Scenario: 永久性錯誤不重試
- **WHEN** 發文 API 回傳永久性錯誤（token 失效 401/190、rate limit 429）
- **THEN** 系統 SHALL 不進行重試，直接依現行行為標記貼文為 `failed`（並視情況標記帳號為 `needs_reauth`）
