## Purpose

以 Polling 方式定期拉取各綁定 Threads 帳號收到的回覆，集中收錄至平台資料庫，供管理員在集中面板查看與回應。

## ADDED Requirements

### Requirement: 定期拉取回覆
系統 SHALL 透過排程（Scheduler）定期對每個已綁定帳號的最新貼文執行回覆拉取，並將新回覆存入資料庫，避免重複入庫。

#### Scenario: 拉取到新回覆
- **WHEN** 排程 Job 執行回覆拉取，且 Threads API 回傳包含新回覆（threads_reply_id 不存在於本地資料庫）
- **THEN** 系統建立新的回覆記錄（threads_reply_id、author_username、text、所屬貼文、來源標記為 `polling`）

#### Scenario: 無新回覆
- **WHEN** 排程 Job 執行回覆拉取，但所有回覆都已存在於本地資料庫
- **THEN** 系統不建立重複記錄，更新該帳號的 last_synced_at 時間

#### Scenario: token 失效導致拉取失敗
- **WHEN** 拉取回覆時 Threads API 回傳 401
- **THEN** 系統跳過該帳號，將帳號標記為 `needs_reauth`，記錄錯誤日誌

### Requirement: 回覆拉取頻率可控
系統 SHALL 提供設定項控制 Polling 間隔（預設每 5 分鐘），且每個帳號獨立記錄最後同步時間，避免重複拉取。

#### Scenario: 拉取間隔內不重複執行
- **WHEN** 某帳號距上次同步不足 5 分鐘
- **THEN** 系統跳過該帳號的拉取，等待下次排程

### Requirement: 新回覆標記
系統 SHALL 將新拉取的回覆標記為「未讀」（status = `new`），供管理員在回覆面板快速識別尚未處理的回覆。

#### Scenario: 新回覆入庫
- **WHEN** 系統建立新的回覆記錄
- **THEN** 回覆的狀態為 `new`，在面板上以視覺標示區別（例如高亮或標籤）
