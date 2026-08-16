# 使用說明與 MCP 控管 實作計畫

> **面向 AI 代理的工作者：** 必需子技能：使用 superpowers:subagent-driven-development（推薦）或 superpowers:executing-plans 逐任務實作此計畫。步驟使用複選框（`- [ ]`）語法來追蹤進度。

**目標：** 新增「使用說明」頁面（Filament Page）與「MCP 控管」Resource（Filament Resource），讓營運人員可查看操作說明，並管理自己的 MCP OAuth token。

**架構：** 「使用說明」以 Filament Page + Blade view 實作，單欄長文件排版；「MCP 控管」以完整 Filament Resource 實作，直接使用 `Laravel\Passport\Token` 為 model，列表顯示 Token ID、來源、Client ID、授權範圍、時間、狀態，並提供註銷動作。

**技術棧：** Laravel 13、Filament 5、`laravel/passport`、PHPUnit + Livewire 測試。

---

## 現狀說明

- 導覽目前 4 個 Resource（Threads App / Threads 帳號 / 排程發文 / 回覆面板），扁平排列，無 navigation groups。
- 專案 Filament Resource 慣例：`app/Filament/Resources/<Name>/<Name>Resource.php`，子目錄 `Pages/`、`Tables/`、`Schemas/`。
- Heroicon 使用 `Outlined` 前綴（`Heroicon::OutlinedBookOpen`）。
- Passport 已就緒：`oauth_access_tokens`（`id=char(80)`、`user_id`、`client_id` uuid）、`oauth_clients`（`HasUuids`）。
- `Passport\Token`：`$keyType='string'`、`$incrementing=false`、`client()` BelongsTo、`user()` BelongsTo、`revoke()` 方法。
- `Passport\Client`：`$guarded=false`（所有屬性 fillable）、`$hidden=['secret']`、內建 factory。

---

### 任務 1：建立使用說明頁面

**檔案：**
- 建立：`app/Filament/Pages/UsageGuide.php`
- 建立：`resources/views/filament/pages/usage-guide.blade.php`

- [ ] **步驟 1.1：建立 Filament Page**

執行：
```bash
php artisan make:filament-page UsageGuide --no-interaction
```

預期：產生 `app/Filament/Pages/UsageGuide.php` 與 `resources/views/filament/pages/usage-guide.blade.php`。

- [ ] **步驟 1.2：設定 Page class 屬性**

修改 `app/Filament/Pages/UsageGuide.php`：

```php
<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class UsageGuide extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static ?string $navigationLabel = '使用說明';

    protected static ?int $navigationSort = 60;

    protected static ?string $title = '使用說明';

    protected static string $view = 'filament.pages.usage-guide';
}
```

- [ ] **步驟 1.3：撰寫使用說明 Blade view**

編輯 `resources/views/filament/pages/usage-guide.blade.php`：

```blade
<div class="max-w-4xl mx-auto space-y-12 py-8">
    {{-- 一、前置準備 --}}
    <section>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">一、前置準備：申請 Meta App</h2>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-4">
            <div class="flex items-start gap-3">
                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-400 text-sm font-semibold shrink-0 mt-0.5">1</span>
                <div>
                    <p class="font-medium text-gray-900 dark:text-white">前往 Meta for Developers 網站</p>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">開啟瀏覽器，進入 <a href="https://developers.facebook.com/" target="_blank" class="text-primary-600 dark:text-primary-400 underline">https://developers.facebook.com/</a>，使用你的 Facebook 帳號登入。</p>
                </div>
            </div>

            <div class="flex items-start gap-3">
                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-400 text-sm font-semibold shrink-0 mt-0.5">2</span>
                <div>
                    <p class="font-medium text-gray-900 dark:text-white">建立應用程式</p>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">點擊右上角「我的應用程式」→「建立應用程式」→ 選擇「Threads」use case → 填寫應用程式名稱 → 點擊「建立應用程式」。</p>
                </div>
            </div>

            <div class="flex items-start gap-3">
                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-400 text-sm font-semibold shrink-0 mt-0.5">3</span>
                <div>
                    <p class="font-medium text-gray-900 dark:text-white">取得應用程式編號與密鑰</p>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">建立完成後，在左側選單點擊「應用程式設定」→「基本資料」，即可看到「應用程式編號」（App ID）與「應用程式密鑰」（App Secret）。請將這兩個值記下來，後面會用到。</p>
                </div>
            </div>

            <div class="flex items-start gap-3">
                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-400 text-sm font-semibold shrink-0 mt-0.5">4</span>
                <div>
                    <p class="font-medium text-gray-900 dark:text-white">加入 Threads 測試人員</p>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">在左側選單點擊「應用程式角色」→「角色」→ 點擊「新增人員」→ 輸入要管理的 Threads 帳號所屬的 Facebook 帳號 → 選擇「Threads 測試人員」→ 點擊「新增」。這樣系統就能以該帳號的身分發文與回覆。</p>
                </div>
            </div>

            <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4 mt-4">
                <p class="text-sm text-amber-800 dark:text-amber-200"><strong>注意：</strong>測試階段使用 Threads 測試人員即可操作。如果未來要讓一般使用者也能使用，需通過 Meta 的 App Review 審查，取得 Advanced Access 權限。</p>
            </div>
        </div>
    </section>

    {{-- 二、本系統設定 --}}
    <section>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">二、在本系統設定 Threads App 與綁定帳號</h2>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-4">
            <div class="flex items-start gap-3">
                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-400 text-sm font-semibold shrink-0 mt-0.5">1</span>
                <div>
                    <p class="font-medium text-gray-900 dark:text-white">新增 Threads App</p>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">進入左側選單「Threads App」頁面 → 點擊「新增」→ 填入 App 名稱（自行命名，方便辨識即可）、應用程式編號（步驟一取得的 App ID）、應用程式密鑰（步驟一取得的 App Secret）→ 點擊「建立」。</p>
                </div>
            </div>

            <div class="flex items-start gap-3">
                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-400 text-sm font-semibold shrink-0 mt-0.5">2</span>
                <div>
                    <p class="font-medium text-gray-900 dark:text-white">綁定 Threads 帳號</p>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">在 Threads App 列表中找到剛剛新增的 App，點擊「綁定 Threads 帳號」→ 系統會跳轉到 Threads 授權頁面 → 點擊「允許」→ 自動導回後台，綁定完成。</p>
                </div>
            </div>

            <div class="flex items-start gap-3">
                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-400 text-sm font-semibold shrink-0 mt-0.5">3</span>
                <div>
                    <p class="font-medium text-gray-900 dark:text-white">重新授權（當 token 失效時）</p>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">如果帳號狀態顯示「需重新授權」，在「Threads 帳號」頁面點擊「重新授權」即可更新 token，不需先解除綁定。</p>
                </div>
            </div>
        </div>
    </section>

    {{-- 三、排程發文 --}}
    <section>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">三、排程發文</h2>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-4">
            <div class="flex items-start gap-3">
                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-400 text-sm font-semibold shrink-0 mt-0.5">1</span>
                <div>
                    <p class="font-medium text-gray-900 dark:text-white">建立排程貼文</p>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">進入「排程發文」頁面 → 點擊「新增」→ 選擇目標帳號、輸入貼文內容（純文字，最多 500 字元）、設定發佈時間 → 點擊「建立」。</p>
                </div>
            </div>

            <div class="flex items-start gap-3">
                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-400 text-sm font-semibold shrink-0 mt-0.5">2</span>
                <div>
                    <p class="font-medium text-gray-900 dark:text-white">貼文狀態說明</p>
                    <div class="mt-2 overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-700/50">
                                <tr>
                                    <th class="px-4 py-2 text-left font-medium text-gray-700 dark:text-gray-300">狀態</th>
                                    <th class="px-4 py-2 text-left font-medium text-gray-700 dark:text-gray-300">說明</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr><td class="px-4 py-2 text-gray-600 dark:text-gray-400">草稿</td><td class="px-4 py-2 text-gray-900 dark:text-white">貼文已儲存但尚未排程</td></tr>
                                <tr><td class="px-4 py-2 text-gray-600 dark:text-gray-400">排程中</td><td class="px-4 py-2 text-gray-900 dark:text-white">已設定發佈時間，等待系統觸發</td></tr>
                                <tr><td class="px-4 py-2 text-gray-600 dark:text-gray-400">發佈中</td><td class="px-4 py-2 text-gray-900 dark:text-white">系統正在將貼文發佈到 Threads（約需 30 秒）</td></tr>
                                <tr><td class="px-4 py-2 text-gray-600 dark:text-gray-400">已發佈</td><td class="px-4 py-2 text-gray-900 dark:text-white">貼文已成功發佈到 Threads</td></tr>
                                <tr><td class="px-4 py-2 text-gray-600 dark:text-gray-400">失敗</td><td class="px-4 py-2 text-gray-900 dark:text-white">發佈失敗，系統會自動重試（最多 3 次）</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="flex items-start gap-3">
                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-400 text-sm font-semibold shrink-0 mt-0.5">3</span>
                <div>
                    <p class="font-medium text-gray-900 dark:text-white">自動發佈機制</p>
                    <div class="mt-2 bg-gray-50 dark:bg-gray-700/30 rounded-lg p-4">
                        <p class="text-gray-600 dark:text-gray-400 text-sm">● 系統每 <strong>1 分鐘</strong> 自動檢查一次是否有到期的貼文</p>
                        <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">● 發佈流程：建立媒體容器 → 等待約 <strong>30 秒</strong> → 正式發佈</p>
                        <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">● 失敗時自動重試，最多 <strong>3 次</strong>（間隔 60 秒 / 120 秒 / 180 秒）</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- 四、回覆收集 --}}
    <section>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">四、回覆收集</h2>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-4">
            <div class="flex items-start gap-3">
                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-400 text-sm font-semibold shrink-0 mt-0.5">1</span>
                <div>
                    <p class="font-medium text-gray-900 dark:text-white">收集範圍</p>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">系統只會自動收集「<strong>本系統發出的貼文</strong>」底下的回覆。如果你在 Threads App 上手動發的貼文，其回覆不會被收集。</p>
                </div>
            </div>

            <div class="flex items-start gap-3">
                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-400 text-sm font-semibold shrink-0 mt-0.5">2</span>
                <div>
                    <p class="font-medium text-gray-900 dark:text-white">收集頻率</p>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">系統每 <strong>5 分鐘</strong> 自動檢查一次是否有新的回覆。新的回覆會自動出現在「回覆面板」頁面。</p>
                </div>
            </div>

            <div class="flex items-start gap-3">
                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-400 text-sm font-semibold shrink-0 mt-0.5">3</span>
                <div>
                    <p class="font-medium text-gray-900 dark:text-white">處理回覆</p>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">在「回覆面板」頁面：點擊「回覆」輸入內容送出、或點擊「忽略」標記不需處理的回覆。</p>
                </div>
            </div>
        </div>
    </section>

    {{-- 五、MCP 服務 --}}
    <section>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">五、MCP 服務設定（AI 工具串接）</h2>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-4">
            <p class="text-gray-600 dark:text-gray-400">MCP（Model Context Protocol）是一種讓 AI 工具（如 ChatGPT、Claude Desktop）可以直接操作本系統的協定。系統支援兩種連線方式：</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                    <h4 class="font-medium text-gray-900 dark:text-white">本地模式（開發者）</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">在本機直接啟動，無需網路。開發者或本機 AI 工具使用。</p>
                </div>
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                    <h4 class="font-medium text-gray-900 dark:text-white">HTTP 模式（遠端）</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">透過網路連線，使用 OAuth 2.1 認證。適合遠端 AI 服務使用。</p>
                </div>
            </div>

            <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                <h3 class="font-medium text-gray-900 dark:text-white mb-3">Claude Desktop 設定步驟</h3>
                <div class="bg-gray-50 dark:bg-gray-700/30 rounded-lg p-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">1. 開啟 Claude Desktop 設定檔</p>
                    <pre class="bg-gray-900 text-gray-100 text-xs p-3 rounded overflow-x-auto"><code># macOS: ~/Library/Application Support/Claude/claude_desktop_config.json
# Windows: %APPDATA%\Claude\claude_desktop_config.json</code></pre>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-3 mb-2">2. 加入 MCP 伺服器設定</p>
                    <pre class="bg-gray-900 text-gray-100 text-xs p-3 rounded overflow-x-auto"><code>{
  "mcpServers": {
    "threads": {
      "command": "php",
      "args": ["/你的專案路徑/artisan", "mcp:start", "threads"]
    }
  }
}</code></pre>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-3">3. 將 <code class="bg-gray-200 dark:bg-gray-600 px-1 rounded text-xs">/你的專案路徑/artisan</code> 換成實際的專案路徑。重啟 Claude Desktop 即可使用。</p>
                </div>
            </div>

            <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                <h3 class="font-medium text-gray-900 dark:text-white mb-3">ChatGPT 設定步驟</h3>
                <div class="bg-gray-50 dark:bg-gray-700/30 rounded-lg p-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">1. 在 ChatGPT 中點擊「設定」→「MCP 伺服器」→「新增伺服器」</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">2. 選擇「本機指令」（Stdio）類型</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">3. 填入以下資訊：</p>
                    <div class="bg-gray-100 dark:bg-gray-600/30 rounded p-3 text-sm text-gray-700 dark:text-gray-300 mt-2">
                        <p><strong>名稱：</strong>Threads 管理</p>
                        <p><strong>指令：</strong><code>php</code></p>
                        <p><strong>參數：</strong><code>/你的專案路徑/artisan mcp:start threads</code></p>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-3">4. 儲存後即可在對話中呼叫本系統的功能（查詢帳號、建立排程貼文、查詢回覆等）。</p>
                </div>
            </div>

            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mt-4">
                <p class="text-sm text-blue-800 dark:text-blue-200"><strong>提示：</strong>如果需要遠端（HTTP）模式連線，請先在「MCP 控管」頁面管理 OAuth token。</p>
            </div>
        </div>
    </section>
</div>
```

- [ ] **步驟 1.4：執行 pint 驗證**

運行：
```bash
vendor/bin/pint --dirty --format agent
```

預期：無語法或格式錯誤。

---

### 任務 2：建立 MCP 控管 Resource

**檔案：**
- 建立：`app/Filament/Resources/McpTokens/McpTokenResource.php`
- 建立：`app/Filament/Resources/McpTokens/Pages/ListMcpTokens.php`
- 建立：`app/Filament/Resources/McpTokens/Tables/McpTokensTable.php`

- [ ] **步驟 2.1：建立 Resource 骨架**

執行：
```bash
php artisan make:filament-resource "McpTokens" --no-interaction --resource-namespace "App\\Filament\\Resources"
```

預期：產生 `app/Filament/Resources/McpTokens/` 目錄及其下的 Resource、Pages、Tables 檔案。

- [ ] **步驟 2.2：設定 Resource class**

編輯 `app/Filament/Resources/McpTokens/McpTokenResource.php`：

```php
<?php

namespace App\Filament\Resources\McpTokens;

use App\Filament\Resources\McpTokens\Pages\ListMcpTokens;
use App\Filament\Resources\McpTokens\Tables\McpTokensTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Passport\Token;

class McpTokenResource extends Resource
{
    protected static ?string $model = Token::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static ?string $navigationLabel = 'MCP 控管';

    protected static ?string $modelLabel = 'MCP Token';

    protected static ?int $navigationSort = 50;

    public static function table(Table $table): Table
    {
        return McpTokensTable::configure($table);
    }

    /**
     * 只顯示當前登入使用者的 token。
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMcpTokens::route('/'),
        ];
    }
}
```

- [ ] **步驟 2.3：設定 Table class**

編輯 `app/Filament/Resources/McpTokens/Tables/McpTokensTable.php`：

```php
<?php

namespace App\Filament\Resources\McpTokens\Tables;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Laravel\Passport\Token;

class McpTokensTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('Token ID')
                    ->formatStateUsing(fn (string $state): string => substr($state, 0, 12).'…')
                    ->tooltip(fn (Token $record): string => $record->id),

                TextColumn::make('client.name')
                    ->label('來源')
                    ->searchable(),

                TextColumn::make('client_id')
                    ->label('Client ID')
                    ->formatStateUsing(fn (string $state): string => substr($state, 0, 12).'…'),

                TextColumn::make('scopes')
                    ->label('授權範圍')
                    ->badge()
                    ->formatStateUsing(fn (?array $state): string => $state ? implode(', ', $state) : '-'),

                TextColumn::make('created_at')
                    ->label('建立時間')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),

                TextColumn::make('expires_at')
                    ->label('到期時間')
                    ->dateTime('Y-m-d H:i')
                    ->placeholder('永久有效'),

                IconColumn::make('revoked')
                    ->label('狀態')
                    ->boolean()
                    ->trueIcon('heroicon-o-x-circle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success')
                    ->formatStateUsing(fn (bool $state): string => $state ? '已註銷' : '有效'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('revoke')
                    ->label('註銷')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (Token $record): bool => ! $record->revoked)
                    ->requiresConfirmation()
                    ->modalHeading('確認註銷')
                    ->modalDescription('註銷後，該 token 將無法再存取 MCP 服務。確定要註銷嗎？')
                    ->action(function (Token $record): void {
                        $record->revoke();

                        Notification::make()
                            ->title('已註銷')
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
```

- [ ] **步驟 2.4：設定 List page**

編輯 `app/Filament/Resources/McpTokens/Pages/ListMcpTokens.php`：

```php
<?php

namespace App\Filament\Resources\McpTokens\Pages;

use App\Filament\Resources\McpTokens\McpTokenResource;
use Filament\Resources\Pages\ListRecords;

class ListMcpTokens extends ListRecords
{
    protected static string $resource = McpTokenResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
```

- [ ] **步驟 2.5：刪除不必要的檔案**

執行：
```bash
rm -f app/Filament/Resources/McpTokens/Pages/CreateMcpToken.php
rm -f app/Filament/Resources/McpTokens/Pages/EditMcpToken.php
```

- [ ] **步驟 2.6：執行 pint 驗證**

運行：
```bash
vendor/bin/pint --dirty --format agent
```

預期：無語法或格式錯誤。

---

### 任務 3：撰寫測試

**檔案：**
- 建立：`tests/Feature/McpTokenResourceTest.php`

- [ ] **步驟 3.1：建立測試檔案**

建立 `tests/Feature/McpTokenResourceTest.php`：

```php
<?php

namespace Tests\Feature;

use App\Filament\Resources\McpTokens\Pages\ListMcpTokens;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Client;
use Laravel\Passport\Token;
use Livewire\Livewire;
use Tests\TestCase;

class McpTokenResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_mcp_tokens_shows_own_tokens(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $client = Client::factory()->create(['name' => 'Claude Desktop']);

        $myToken = Token::forceCreate([
            'id' => 'abc123def456',
            'user_id' => $user->id,
            'client_id' => $client->id,
            'name' => null,
            'scopes' => ['mcp:use'],
            'revoked' => false,
            'expires_at' => now()->addDays(30),
        ]);

        $otherToken = Token::forceCreate([
            'id' => 'xyz789ghi012',
            'user_id' => $otherUser->id,
            'client_id' => $client->id,
            'name' => null,
            'scopes' => ['mcp:use'],
            'revoked' => false,
            'expires_at' => now()->addDays(30),
        ]);

        Livewire::actingAs($user)
            ->test(ListMcpTokens::class)
            ->assertCanSeeTableRecords([$myToken]);
    }

    public function test_list_mcp_tokens_shows_empty_when_no_tokens(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ListMcpTokens::class)
            ->assertOk();
    }

    public function test_revoke_token(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['name' => 'Claude Desktop']);

        $token = Token::forceCreate([
            'id' => 'revoke-me-token',
            'user_id' => $user->id,
            'client_id' => $client->id,
            'name' => null,
            'scopes' => ['mcp:use'],
            'revoked' => false,
            'expires_at' => now()->addDays(30),
        ]);

        Livewire::actingAs($user)
            ->test(ListMcpTokens::class)
            ->callAction('revoke', $token);

        $this->assertTrue($token->fresh()->revoked);
    }

    public function test_cannot_see_other_user_tokens(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $client = Client::factory()->create(['name' => 'ChatGPT']);

        $otherToken = Token::forceCreate([
            'id' => 'other-user-token',
            'user_id' => $otherUser->id,
            'client_id' => $client->id,
            'name' => null,
            'scopes' => ['mcp:use'],
            'revoked' => false,
            'expires_at' => now()->addDays(30),
        ]);

        Livewire::actingAs($user)
            ->test(ListMcpTokens::class)
            ->assertCanNotSeeTableRecords([$otherToken]);
    }
}
```

- [ ] **步驟 3.2：執行測試**

運行：
```bash
php artisan test --compact tests/Feature/McpTokenResourceTest.php
```

預期：全部 4 個測試通過。

---

### 任務 4：文件與收斂

- [ ] **步驟 4.1：執行完整測試**

運行：
```bash
php artisan test --compact
```

預期：既有測試與新測試全部通過。

- [ ] **步驟 4.2：執行 pint 修正格式**

運行：
```bash
vendor/bin/pint --format agent
```

預期：無錯誤。

- [ ] **步驟 4.3：回報完成**

依使用者偏好，提供「建議的 commit 訊息」與「變更檔案清單」，不主動 commit。
