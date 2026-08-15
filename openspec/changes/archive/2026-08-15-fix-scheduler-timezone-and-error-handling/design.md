## Context

目前 `threads:schedule` 命令的 `handle()` 方法直接依序呼叫三個 dispatch 方法，沒有任何例外處理。當 `CollectThreadsReplies::dispatch()` 因建構子簽名不符等原因拋出 `ArgumentCountError` 時，整個命令崩潰（exit code 1），導致 `dispatchDuePosts()` 結果也被丟棄。

同時，`config/app.php` 的 `timezone` 設為 `UTC`，而使用者位於台灣時區（UTC+8），造成 `scheduled_at` 時間與直覺有 8 小時落差。

## Goals / Non-Goals

**Goals:**
- 將應用時區改為 `Asia/Taipei`，讓 `now()`、Carbon、排程等時間計算與使用者所在時區一致
- 為 `RunThreadsScheduler` 的三個 dispatch 呼叫加入 try-catch，確保單點失敗不擴散

**Non-Goals:**
- 不改變排程頻率（維持 `everyMinute()`）
- 不改變 Job dispatch 的方式或參數
- 不在 Job 內部加入額外的重試或容錯邏輯
- 不處理資料庫中已存在的 UTC 時間資料遷移

## Decisions

### Decision 1: 時區直接改 config/app.php，不用 env 變數

**選擇**: 直接將 `config/app.php` 的 `'timezone' => 'UTC'` 改為 `'timezone' => 'Asia/Taipei'`

**替代方案**: 使用 `APP_TIMEZONE` env 變數
- 優點：部署彈性，不同環境可用不同時區
- 缺點：增加設定複雜度；目前只有台灣使用者，不需此彈性

**理由**: 專案為單一區域使用，直接寫死在 config 最簡潔。若未來需要多時區，再重構為 env 變數。

### Decision 2: try-catch 放在 RunThreadsScheduler 的各 dispatch 方法內

**選擇**: 在 `dispatchDuePosts()`、`dispatchReplyCollection()`、`dispatchTokenRefresh()` 三個方法各自加入 try-catch，而非在 `handle()` 統一包一層

**替代方案**: 在 `handle()` 中對三次呼叫分別 try-catch
- 優點：集中管理
- 缺點：handle() 會變冗長

**理由**: 每個 dispatch 方法有獨立的失敗語意，分開處理利於錯誤訊息的精確性，也方便後續為不同方法加入不同的錯誤處理策略。

### Decision 3: 失敗時記錄 warning 層級 log

**選擇**: catch 到例外時使用 `Log::warning()` 記錄，附上 context（例外訊息）

**替代方案**: 使用 `Log::error()`
- 缺點：dispatch 失敗不代表系統故障（可能是暫時的類別載入問題），warning 更符合語意

**理由**: warning 層級表達「需要關注但非系統性故障」，符合 dispatch 失敗的嚴重程度。

## Risks / Trade-offs

- **已存在的 `scheduled_at` 資料仍是 UTC 時間**：時區變更後，舊資料的時間語意會改變（原本 `2026-08-15T11:03:00Z` 會被解讀為 `2026-08-15 19:03 Asia/Taipei`）。→ 開發階段資料量極少，可直接手動更新或重建
- **try-catch 可能隱藏結構性問題**：若某個 dispatch 持續失敗，排程不會報錯但實際功能不運作。→ catch 中寫入 log 確保可追蹤；若需更積極的通知機制，後續可加入
