## Context

`ThreadsClient::request()` 是所有 Threads Graph API 呼叫的單一入口（見 proposal.md - Why）。目前它組裝 `$options`（GET/DELETE 用 `query`，POST 用 `form_params`）後呼叫 Guzzle `$this->http->request()`，失敗時拋出 `ThreadsApiException`。此處是記錄完整 curl 內容的最佳切入點。

## Goals / Non-Goals

**Goals:**
- 在 `request()` 中記錄每次請求的完整 curl 內容（method、URL、參數）。
- 失敗時額外記錄回應狀態碼與 body。
- 不改變既有請求發送與錯誤處理行為。

**Non-Goals:**
- 不新增獨立的 curl log 檔案（依使用者選擇，寫入 Laravel log）。
- 不做任何請求重試或錯誤分類的變更。
- 不記錄敏感欄位以外的內容（access_token 仍會記錄，因這是除錯所需）。

## Decisions

### Decision 1: 在 `request()` 內記錄 curl 內容
- **做法**：在 `request()` 中，於呼叫 `$this->http->request()` 前，將 method、完整 URL（`$base.$path`）、參數組合成 curl 描述字串，用 `Log::info()` 記錄。
- **理由**：`request()` 是所有 API 呼叫的單一入口，在此記錄可涵蓋所有請求類型，不需在各個公開方法重複。
- **替代方案**：在各公開方法記錄。缺點是重複且易漏。

### Decision 2: 失敗時記錄回應資訊
- **做法**：在 catch `ClientException` 時，將回應狀態碼與 body 一併記錄；在 `decode()` 偵測到 API error 時，記錄回應狀態碼與 body。
- **理由**：除錯時需要知道 API 實際回傳的錯誤內容，而不只是請求本身。
- **替代方案**：僅記錄請求。缺點是無法得知 API 端錯誤細節。

### Decision 3: 使用 `Log::info()` 記錄
- **做法**：使用 Laravel 的 `Log` facade 寫入預設 log（`storage/logs/laravel.log`）。
- **理由**：符合需求「記錄到 log」，且不需額外設定。
- **替代方案**：使用獨立 channel。需求明確為 Laravel log，故不採用。

## Risks / Trade-offs

- **[log 可能包含 access_token]** → 此為暫時性除錯記錄，且 log 僅開發環境可讀；若需移除，直接刪除記錄程式碼即可。
- **[log 量增加]** → 每次發文請求多一筆 log，量級可接受；此為暫時性功能。
