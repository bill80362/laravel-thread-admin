## Purpose

將 Threads Graph API 請求的完整 curl 內容記錄到 Laravel log，供排程發文失敗時除錯使用。

## ADDED Requirements

### Requirement: 記錄完整 curl 請求內容
系統 SHALL 在每次呼叫 Threads Graph API 時，將該請求的完整 curl 內容（HTTP method、完整 URL、query/form 參數）記錄到 Laravel log。

#### Scenario: 成功請求也記錄 curl
- **WHEN** `ThreadsClient` 對 Threads Graph API 發出任何請求（GET、POST、DELETE）
- **THEN** 系統 SHALL 在 log 中記錄該請求的 method、完整 URL 與參數

#### Scenario: 失敗請求記錄回應資訊
- **WHEN** Threads API 請求失敗（回傳錯誤狀態碼或網路錯誤）
- **THEN** 系統 SHALL 在 log 中記錄回應的狀態碼與回應 body（若可取得）
- **AND** 既有錯誤處理行為（拋出 `ThreadsApiException`）不受影響
