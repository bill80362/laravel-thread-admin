# 使用說明與 MCP 控管 — 設計規格

> 日期：2026-08-16
> 對應 OpenSpec change：`add-usage-guide-and-mcp-management`

## 一、使用說明頁面

### 實作方式

- **Filament 自訂 Page**（`php artisan make:filament-page UsageGuide`）
- Page class：`app/Filament/Pages/UsageGuide.php`
- Blade view：`resources/views/filament/pages/usage-guide.blade.php`

### 導覽設定

| 屬性 | 值 |
|------|-----|
| `$navigationIcon` | `Heroicon::OutlinedBookOpen` |
| `$navigationLabel` | `使用說明` |
| `$navigationSort` | `60` |
| `$title` | `使用說明` |

### 排版

**單欄長文件**，五大章節由上而下連續捲動，不設側邊目錄或手風琴摺疊。

### 內容架構（五大章節）

#### 一、前置準備：申請 Meta App

以非程式語言撰寫，包含：
- Meta for Developers 網站網址（`https://developers.facebook.com/`）
- 建立應用程式 → 選擇「Threads」use case
- 取得「應用程式編號」（App ID）與「應用程式密鑰」（App Secret）的位置
- 在 App Dashboard → 角色 → 新增「Threads 測試人員」，將要管理的 Threads 帳號加入
- 注意事項：測試階段使用 Threads Tester 即可，正式上線需通過 App Review

#### 二、本系統設定 Threads App 與綁定帳號

- 進入「Threads App」頁面 → 新增 → 填入名稱、應用程式編號、應用程式密鑰
- 點擊「綁定 Threads 帳號」→ 在 Threads 授權頁同意 → 自動導回
- 說明 redirect_uri 由系統自動處理（`APP_URL` + `/threads/oauth/callback`），無需手動設定
- 重新授權：token 失效時點擊「重新授權」即可

#### 三、排程發文

- 狀態機流程圖（文字描述）：
  ```
  草稿 → 排程中 → 發佈中（等待約 30 秒）→ 已發佈
                    ↓（失敗）
                  失敗（自動重試，最多 3 次）
  ```
- 觸發頻率：系統每分鐘自動檢查到期貼文
- 發佈流程：先建立媒體容器 → 等待約 30 秒 → 正式發佈到 Threads
- 失敗處理：自動重試最多 3 次，每次間隔遞增（60 秒、120 秒、180 秒）
- 字數限制：純文字貼文上限 500 字元

#### 四、回覆收集

- 偵測範圍：**只收集本系統發出的貼文**的回覆（不含帳號在 Threads 上自行手動發的貼文）
- 偵測頻率：每 5 分鐘自動檢查一次
- 新回覆會自動出現在「回覆面板」，可快速回覆或標記忽略

#### 五、MCP 服務設定

- 說明 MCP 是什麼（讓 AI 工具可以直接操作本系統）
- 兩種模式：
  - **本地模式**：`php artisan mcp:start threads`（適合開發者本機使用）
  - **HTTP 模式**：透過 OAuth 2.1 認證（適合遠端 AI 服務）
- ChatGPT 註冊步驟（一步一步教學，含設定檔範例）
- Claude Desktop 註冊步驟（一步一步教學，含 `claude_desktop_config.json` 範例）

### 撰寫原則

- 繁體中文、口語化
- 步驟編號（1. 2. 3.）
- 避免程式術語（class、namespace、migration 等）
- 關鍵數值（排程間隔、重試次數等）直接引用程式碼常數，確保與實作一致

---

## 二、MCP 控管頁面

### 實作方式

- **完整 Filament Resource**（`php artisan make:filament-resource McpToken`）
- Model：`Laravel\Passport\Token`（直接使用，不建立自訂 model）
- 僅保留 `index` 頁面（唯讀列表），移除 create / edit

### 導覽設定

| 屬性 | 值 |
|------|-----|
| `$model` | `Laravel\Passport\Token::class` |
| `$navigationIcon` | `Heroicon::OutlinedKey` |
| `$navigationLabel` | `MCP 控管` |
| `$navigationSort` | `50` |
| `$modelLabel` | `MCP Token` |

### 權限隔離

```php
public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()->where('user_id', auth()->id());
}
```

### 列表欄位（技術完整）

| 欄位 | 來源 | 顯示方式 |
|------|------|---------|
| Token ID | `id` | TextColumn，截斷顯示（前 8 字元 + …） |
| 來源 | `client.name`（關聯 `oauth_clients`） | TextColumn |
| Client ID | `client_id` | TextColumn，截斷顯示 |
| 授權範圍 | `scopes`（array cast） | TextColumn，badge 顯示 |
| 建立時間 | `created_at` | TextColumn，datetime |
| 到期時間 | `expires_at` | TextColumn，datetime |
| 狀態 | `revoked`（boolean） | IconColumn（有效/已註銷） |

### 動作

- **註銷**：`Action::make('revoke')`，呼叫 `$record->revoke()`，成功後 `Notification::make()->success()` 並 `->refresh()`
- 僅對 `revoked = false` 的 token 顯示註銷按鈕

### 安全注意

- `Laravel\Passport\Client::$hidden = ['secret']`，Client secret 不會出現在列表
- `getEloquentQuery()` 限定 `user_id`，天然隔離

---

## 三、導覽結構（最終）

```
Dashboard
├── Threads App          (sort: 10)
├── Threads 帳號          (sort: 20)
├── 排程發文              (sort: 30)
├── 回覆面板              (sort: 40)
├── 🔑 MCP 控管          (sort: 50)  ← 新增
└── 📖 使用說明           (sort: 60)  ← 新增
```

不分 navigation groups，維持扁平排列。

---

## 四、測試

- `tests/Feature/McpTokenResourceTest.php`：
  - 登入使用者只能看到自己的 token
  - 註銷動作成功
  - 空清單顯示正常
  - 無法看到其他使用者的 token

---

## 五、文件更新

- `AGENTS.md`：加入使用說明與 MCP 控管開發規範
  - 使用說明內容需與排程常數同步
  - MCP 控管不洩漏 Client secret
