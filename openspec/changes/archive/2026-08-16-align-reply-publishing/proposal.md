## Why

目前後台「回覆面板」與 MCP 的「建立回覆」工具建立出來的回覆記錄只會寫入本機資料庫、停留在 `new` 狀態，**不會實際發佈到 Threads**。這造成使用者「新增一筆回覆」後以為內容已發佈，實際上什麼都沒發生。此外，介面與 MCP 對同一件事使用了不同名詞（「新增回覆」vs「建立手動回覆記錄」、「回覆」vs「回應」），讓「收到的留言」與「發出去的回覆」兩種概念混淆不清。

## What Changes

- 後台 `/admin/replies` 的新增按鈕改名為「新增貼文回覆」，其表單欄位調整為：移除「留言者」（`author_username`），「所屬貼文」（`post_id`）改為必填。
- 後台回覆列表每一筆記錄上的動作按鈕「回覆」改名為「回應回覆」，行為維持為「回覆該筆留言」。
- 「新增貼文回覆」與「回應回覆」兩者 SHALL 都實際呼叫 Threads API 發佈（`createTextContainer` + `publishContainer`），不再只寫入本機資料庫。
- 將發佈邏輯收斂到 `ReplyService`，讓後台與 MCP 共用同一套發佈與錯誤處理規則。
- MCP `create-reply` 工具改名／改描述為「建立貼文回覆」，參數對齊後台表單（移除 `author_username`、`post_id` 改必填），行為與介面一致。
- `ReplyStatus` 視發佈流程需要擴充狀態（如 `publishing`／`failed`），以便追蹤發佈進度與失敗。（**BREAKING**：`Reply` 的狀態語義從「是否已回覆該留言」擴充為「回覆的發佈狀態」）
- 「回應回覆」與「新增貼文回覆」的 `reply_to_id` 來源不同：前者取自該留言的 `threads_reply_id`，後者取自所屬貼文的 `threads_media_id`。

## Capabilities

### New Capabilities
- `reply-publishing`: 定義「貼文回覆」與「回應回覆」兩種回覆發佈到 Threads 的行為，包括兩階段發佈、狀態轉換、失敗與重試處理，以及後台與 MCP 共用 Service 的規則。

### Modified Capabilities
- `reply-manual-create`: 手動新增回覆的語義從「建立一筆手動回覆記錄」改為「新增貼文回覆並發佈」，表單欄位（移除 `author_username`、`post_id` 改必填）與按鈕名詞（「新增貼文回覆」）變更。
- `mcp-server`: `create-reply` 工具的語義與參數對齊「新增貼文回覆」，名稱與描述同步更新，行為與介面一致。

## Impact

- 受影響程式碼：`app/Filament/Resources/Replies/`（`ReplyForm`、`RepliesTable`、`ReplyResource`、`CreateReply` 頁面）、`app/Mcp/Tools/CreateReplyTool.php`、`app/Services/ReplyService.php`、`app/Enums/ReplyStatus.php`、`app/Models/Reply.php`。
- 可能新增：回覆發佈 Job（若採用非同步發佈）、`ReplyStatus` 新狀態的 migration。
- 資料模型：`replies` 表可能需新增發佈相關欄位（如 `publish_attempts`、`error_message`、發佈目標 ID），視設計階段決定。
- API 依賴：`ThreadsClient::createTextContainer` 與 `publishContainer` 已支援，無需變更。
- 影響既有資料：現有 `source=manual` 且 `threads_reply_id` 為空的歷史記錄（如 id=4、6）在新語義下不再適用，設計階段需決定遷移或清理方式。
