# 每日用量限制與紀錄查詢

## 架構總覽

```
┌─ 建立排程 ─────────────────────────────────────┐
│  PostService::create() / ReplyService::create()  │
│  → MCP 回傳 warnings 陣列（軟性警告）             │
│  → 介面列表頁頂部用量條（視覺提示）                │
└──────────────────────┬──────────────────────────┘
                       │ 排程 job
                       ▼
┌─ 發送時（Job stage1）───────────────────────────┐
│  PublishScheduledPost / PublishReply             │
│  → 檢查 activity_logs 今日 count >= max_daily   │
│  → 超額 → 標記 Failed，不呼叫 API               │
│  → 正常 → 建立 container，繼續                   │
└──────────────────────┬──────────────────────────┘
                       │ 發送成功
                       ▼
┌─ 發送成功（Job stage2）─────────────────────────┐
│  → 寫入 activity_logs（type=post/reply）         │
│  → text 反標準化保留（刪除後仍可查）               │
└─────────────────────────────────────────────────┘
```

## 資料表設計

```sql
activity_logs
├── id                  (bigint PK)
├── user_id             (unsignedBigInteger, NOT NULL)
├── threads_account_id  (unsignedBigInteger, NOT NULL)
├── type                (string: 'post' | 'reply')
├── reference_id        (unsignedBigInteger, nullable: post.id 或 reply.id)
├── threads_media_id    (string, nullable)
├── text                (string 500, nullable, 反標準化保留)
└── created_at          (timestamp)
```

- 不使用外鍵約束
- Model 層透過 Eloquent `belongsTo` 關聯

## 寫入時機

| 時機 | 動作 |
|------|------|
| `PublishScheduledPost` stage2 成功 | `type = 'post'` |
| `PublishReply` stage2 成功 | `type = 'reply'` |

## 硬性阻擋

| Job | 檢查點 | 條件 | 結果 |
|-----|--------|------|------|
| `PublishScheduledPost` stage1 | `todayCount >= user.max_daily_posts` | 超額 | 標記 Failed「已達每日發文上限」 |
| `PublishReply` stage1 | `todayCount >= user.max_daily_replies` | 超額 | 標記 Failed「已達每日回覆上限」 |

## 軟性警告

| 端 | 位置 | 內容 |
|----|------|------|
| MCP `CreatePostTool` | 回傳 `warnings` 陣列 | 今日已發 / 上限 / 排程中今日將發送 |
| MCP `CreateReplyTool` | 回傳 `warnings` 陣列 | 今日已回覆 / 上限 |
| User 貼文列表頁 | 頂部用量提示條 | 發文用量條 + 回覆用量條 |

## 查詢介面

| 端 | 位置 | 內容 |
|----|------|------|
| User | 導航「發送紀錄」 | 完整 activity_logs 列表（依時間倒序） |
| Admin | User Edit 頁 RelationManager | 該使用者的 activity_logs 明細 |
| Admin | User 列表頁 | 今日發文/回覆可點擊看明細 |

## 刪除貼文

- 維持硬刪除（`$post->delete()`）
- `activity_logs` 記錄不變，`reference_id` 變孤兒
- `text` 反標準化欄位保留發文內容
- 計數不受影響

## 限制規則

- 寬鬆限制：不鎖、不處理競爭條件（超發一兩封可接受）
- 不處理歷史 log 清理（未來再說）
- 不阻擋建立排程（僅在發送時檢查）
