## Purpose

讓管理員透過 OAuth 流程綁定 Threads 帳號至平台，並自動管理 token 的生命週期（取得長效 token、到期前自動續命、失敗時標記需重新授權），確保 API 呼叫不會因 token 失效而中斷。

## ADDED Requirements

### Requirement: 管理員可綁定 Threads 帳號
系統 SHALL 提供 OAuth 授權流程，讓管理員將 Threads 帳號綁定至平台。流程為：點擊「綁定帳號」→ 跳轉 Threads 授權頁 → 使用者同意授權 → 回調取得授權碼 → 系統交換短效 token → 再交換長效 token → 儲存帳號資訊。

#### Scenario: 成功綁定新帳號
- **WHEN** 管理員完成 OAuth 授權流程，且 Threads API 成功回傳長效 token
- **THEN** 系統儲存帳號（threads_user_id、username、加密後的 access_token、token_expires_at），並在後台顯示「已綁定」狀態

#### Scenario: 授權被使用者拒絕
- **WHEN** 管理員在 Threads 授權頁點擊「取消」或拒絕授權
- **THEN** 系統顯示錯誤提示「授權已取消」，不建立任何帳號記錄

#### Scenario: token 交換失敗
- **WHEN** 授權碼過期（超過 1 小時）或 Threads API 回傳錯誤
- **THEN** 系統顯示錯誤提示「綁定失敗，請重新授權」，不儲存無效 token

### Requirement: token 到期前自動續命
系統 SHALL 每日檢查所有已綁定帳號的長效 token，對距到期日少於 7 天的 token 自動執行續命（GET /refresh_access_token），並更新 token 與到期日。

#### Scenario: 續命成功
- **WHEN** 排程 Job 對距到期日 ≤7 天的 token 執行續命，且 API 回傳新的長效 token
- **THEN** 系統更新該帳號的 access_token 與 token_expires_at（+60 天）

#### Scenario: 續命失敗（token 已失效）
- **WHEN** 排程 Job 嘗試續命，但 API 回傳錯誤
- **THEN** 系統將該帳號標記為 status = `needs_reauth`，並在後台帳號列表顯示警告圖示與「需重新授權」標籤

### Requirement: 解除綁定 Threads 帳號
系統 SHALL 允許管理員解除綁定不再使用的 Threads 帳號。

#### Scenario: 解除綁定
- **WHEN** 管理員在帳號管理頁點擊「解除綁定」並確認
- **THEN** 系統刪除該帳號的 access_token 與認證資訊，標記為已解除綁定（或軟刪除），該帳號相關的未發排程文章一併取消

### Requirement: token 安全儲存
系統 SHALL 將所有 Threads access token 以加密方式儲存於資料庫，不得以明文存放。

#### Scenario: token 寫入與讀取
- **WHEN** 系統儲存或讀取 access token
- **THEN** token 在資料庫中為加密狀態，僅在應用程式層解密使用
