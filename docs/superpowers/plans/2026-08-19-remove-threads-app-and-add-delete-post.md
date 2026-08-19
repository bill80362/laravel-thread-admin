# 移除 ThreadsApp、修復 OAuth 綁定、新增刪除貼文、簡化 MCP 實作計畫

> **面向 AI 代理的工作者：** 必需子技能：使用 superpowers:subagent-driven-development（推薦）或 superpowers:executing-plans 逐任務實現此計畫。步驟使用複選框（`- [ ]`）語法來追蹤進度。

**目標：** 移除 ThreadsApp 資料庫管理改為 .env 設定、修復 OAuth callback 的 user_id 歸屬、新增刪除 Threads 貼文功能、MCP 僅保留 HTTP 模式

**架構：** 從 `User → ThreadsApp → ThreadsAccount → Post/Reply` 簡化為 `User → ThreadsAccount → Post/Reply`，憑證從 DB 移至 config/services.php + .env，OAuth state 承載 user_id，PostStatus 新增 Deleting/DeleteFailed 狀態

**技術棧：** PHP 8.4 + Laravel 13 + Filament 5 + SQLite + Guzzle + Laravel Queue (database)

---

### 任務 1：環境變數與設定

**檔案：**
- 修改：`.env.example`
- 修改：`config/services.php`

- [ ] **步驟 1：在 .env.example 新增 Threads 憑證範本**

在 `.env.example` 檔案末尾新增：

```env
THREADS_CLIENT_ID=
THREADS_CLIENT_SECRET=
```

- [ ] **步驟 2：在 config/services.php 新增 client_id 與 client_secret**

修改 `config/services.php` 的 `threads` 區塊：

```php
'threads' => [
    'client_id' => env('THREADS_CLIENT_ID'),
    'client_secret' => env('THREADS_CLIENT_SECRET'),
    'redirect_uri' => rtrim((string) config('app.url'), '/').'/threads/oauth/callback',
],
```

- [ ] **步驟 3：Commit**

```bash
git add .env.example config/services.php
git commit -m "feat: add THREADS_CLIENT_ID and THREADS_CLIENT_SECRET to env and config"
```

---

### 任務 2：建立 Migration 移除 ThreadsApp

**檔案：**
- 建立：`database/migrations/2026_08_19_000000_remove_threads_apps_table.php`

- [ ] **步驟 1：建立 migration 檔案**

```bash
php artisan make:migration remove_threads_apps_table --no-interaction
```

- [ ] **步驟 2：編寫 migration 內容**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. 移除 threads_accounts 的 threads_app_id 外鍵與欄位
        Schema::table('threads_accounts', function (Blueprint $table) {
            // SQLite 不支援 dropForeign，但 Laravel 會自動處理
            if (Schema::hasColumn('threads_accounts', 'threads_app_id')) {
                $table->dropForeign(['threads_app_id']);
                $table->dropColumn('threads_app_id');
            }
        });

        // 2. 移除 threads_oauth_states 的 threads_app_id 外鍵與欄位
        Schema::table('threads_oauth_states', function (Blueprint $table) {
            if (Schema::hasColumn('threads_oauth_states', 'threads_app_id')) {
                $table->dropForeign(['threads_app_id']);
                $table->dropColumn('threads_app_id');
            }
        });

        // 3. 刪除 threads_apps 表格
        Schema::dropIfExists('threads_apps');
    }

    public function down(): void
    {
        // 重建 threads_apps 表格
        Schema::create('threads_apps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('client_id');
            $table->text('client_secret');
            $table->timestamps();
        });

        // 恢復 threads_accounts.threads_app_id
        Schema::table('threads_accounts', function (Blueprint $table) {
            $table->foreignId('threads_app_id')->nullable()->after('id')->constrained('threads_apps')->nullOnDelete();
        });

        // 恢復 threads_oauth_states.threads_app_id
        Schema::table('threads_oauth_states', function (Blueprint $table) {
            $table->foreignId('threads_app_id')->nullable()->after('id')->constrained('threads_apps')->nullOnDelete();
        });
    }
};
```

- [ ] **步驟 3：執行 migration 確認成功**

```bash
php artisan migrate --no-interaction
```

- [ ] **步驟 4：執行 rollback 確認可回溯**

```bash
php artisan migrate:rollback --step=1 --no-interaction
php artisan migrate --no-interaction
```

- [ ] **步驟 5：Commit**

```bash
git add database/migrations/2026_08_19_000000_remove_threads_apps_table.php
git commit -m "feat: add migration to remove threads_apps table and related foreign keys"
```

---

### 任務 3：移除 ThreadsApp Model 與相關程式碼

**檔案：**
- 刪除：`app/Models/ThreadsApp.php`
- 修改：`app/Models/ThreadsAccount.php`
- 修改：`app/Models/User.php`
- 修改：`app/Models/OAuthState.php`
- 刪除：`database/factories/ThreadsAppFactory.php`
- 修改：`database/factories/ThreadsAccountFactory.php`
- 刪除：`app/Filament/Resources/ThreadsApps/`（整個目錄）
- 修改：`app/Filament/Resources/ThreadsAccounts/Tables/ThreadsAccountsTable.php`

- [ ] **步驟 1：刪除 ThreadsApp model**

```bash
rm app/Models/ThreadsApp.php
```

- [ ] **步驟 2：修改 ThreadsAccount model — 移除 threadsApp() 關聯與 threads_app_id fillable**

修改 `app/Models/ThreadsAccount.php`：

```php
// 移除 threadsApp() 方法（整個方法區塊）
// 從 $fillable 陣列中移除 'threads_app_id'
// 移除 use App\Models\ThreadsApp 的 import（如果有的話）
```

具體變更：
- 從 `$fillable` 陣列中刪除 `'threads_app_id',`
- 刪除整個 `threadsApp()` 方法及其 PHPDoc
- 刪除 `use App\Models\ThreadsApp;` import（如果存在）

- [ ] **步驟 3：修改 User model — 移除 threadsApps() 關聯**

修改 `app/Models/User.php`：

```php
// 刪除整個 threadsApps() 方法及其 PHPDoc：
// /**
//  * The Threads apps managed by this user.
//  *
//  * @return HasMany<ThreadsApp>
//  */
// public function threadsApps(): HasMany
// {
//     return $this->hasMany(ThreadsApp::class);
// }
```

- [ ] **步驟 4：修改 OAuthState model — 重構 createForApp 與 resolve**

修改 `app/Models/OAuthState.php`：

```php
// 1. 從 $fillable 移除 'threads_app_id'
// 2. 刪除 threadsApp() 方法
// 3. 將 createForApp() 改名為 createForUser()，移除 ThreadsApp $app 參數
// 4. resolve() 移除 where('user_id', auth()->id()) 檢查，回傳型別改為 {user_id, account}

public static function createForUser(?ThreadsAccount $account = null): string
{
    $token = bin2hex(random_bytes(32));

    self::query()->create([
        'token_hash' => hash('sha256', $token),
        'threads_account_id' => $account?->id,
        'user_id' => auth()->id(),
        'expires_at' => now()->addMinutes(10),
    ]);

    return $token;
}

/**
 * @return array{user_id: int, account: ?ThreadsAccount}|null
 */
public static function resolve(string $token): ?array
{
    $state = self::query()
        ->where('token_hash', hash('sha256', $token))
        ->first();

    if ($state === null || $state->expires_at->isPast()) {
        return null;
    }

    $state->delete();

    return [
        'user_id' => $state->user_id,
        'account' => $state->threadsAccount,
    ];
}
```

- [ ] **步驟 5：刪除 ThreadsAppFactory**

```bash
rm database/factories/ThreadsAppFactory.php
```

- [ ] **步驟 6：修改 ThreadsAccountFactory — 移除 threads_app_id**

修改 `database/factories/ThreadsAccountFactory.php`：

```php
// 從 definition() 回傳陣列中刪除 'threads_app_id' => ThreadsApp::factory(),
// 刪除 use App\Models\ThreadsApp; import
```

- [ ] **步驟 7：刪除 ThreadsApp Filament Resource 目錄**

```bash
rm -rf app/Filament/Resources/ThreadsApps/
```

- [ ] **步驟 8：修改 ThreadsAccountsTable — 移除 threadsApp 相關欄位與調整重新授權 URL**

修改 `app/Filament/Resources/ThreadsAccounts/Tables/ThreadsAccountsTable.php`：

```php
// 1. 刪除 TextColumn::make('threadsApp.name') 整個欄位定義
// 2. 刪除 SelectFilter::make('threads_app_id') 整個篩選器定義
// 3. 修改 reauthorizeAction() 中的 URL：
//    從 route('threads.oauth.redirect', ['app' => $record->threads_app_id, 'account' => $record->id])
//    改為 route('threads.oauth.redirect', ['account' => $record->id])
// 4. 移除 reauthorizeAction() 中的 ->visible() 條件（不再需要檢查 threads_app_id）
```

- [ ] **步驟 9：執行 migration fresh 確認 schema 正確**

```bash
php artisan migrate:fresh --no-interaction
```

- [ ] **步驟 10：Commit**

```bash
git add -A
git commit -m "refactor: remove ThreadsApp model, factory, Filament resource, and related code"
```

---

### 任務 4：重構 ThreadsClient 與 OAuth 流程

**檔案：**
- 修改：`app/Services/ThreadsClient.php`
- 修改：`app/Http/Controllers/ThreadsOAuthController.php`
- 修改：`routes/web.php`

- [ ] **步驟 1：重構 ThreadsClient — OAuth 方法改從 config 讀取憑證**

修改 `app/Services/ThreadsClient.php`：

```php
// buildAuthorizationUrl() — 移除 ThreadsApp $app 參數，改從 config 讀取
public function buildAuthorizationUrl(string $state): string
{
    $query = http_build_query([
        'client_id' => Config::get('services.threads.client_id'),
        'redirect_uri' => Config::get('services.threads.redirect_uri'),
        'scope' => implode(',', self::SCOPES),
        'response_type' => 'code',
        'state' => $state,
    ]);

    return 'https://www.threads.net/oauth/authorize?'.$query;
}

// exchangeCodeForShortToken() — 移除 ThreadsApp $app 參數
public function exchangeCodeForShortToken(string $code): string
{
    $data = $this->request('POST', '/oauth/access_token', [
        'client_id' => Config::get('services.threads.client_id'),
        'client_secret' => Config::get('services.threads.client_secret'),
        'grant_type' => 'authorization_code',
        'redirect_uri' => Config::get('services.threads.redirect_uri'),
        'code' => $code,
    ], self::OAUTH_BASE);

    return $data['access_token'];
}

// exchangeShortForLongToken() — 移除 ThreadsApp $app 參數
public function exchangeShortForLongToken(string $shortToken): array
{
    return $this->request('GET', '/access_token', [
        'grant_type' => 'th_exchange_token',
        'client_secret' => Config::get('services.threads.client_secret'),
        'access_token' => $shortToken,
    ], self::OAUTH_BASE);
}
```

同時移除 `use App\Models\ThreadsApp;` import。

- [ ] **步驟 2：重構 ThreadsOAuthController**

修改 `app/Http/Controllers/ThreadsOAuthController.php`：

```php
// redirect() — 不再接收 ThreadsApp $app，改用 config 憑證
public function redirect(Request $request): RedirectResponse
{
    $targetAccount = null;

    if ($accountId = $request->query('account')) {
        $targetAccount = ThreadsAccount::query()
            ->where('id', $accountId)
            ->where('user_id', auth()->id())
            ->first();
    }

    $state = OAuthState::createForUser($targetAccount);

    return redirect()->away($this->threads->buildAuthorizationUrl($state));
}

// callback() — 使用 $resolved['user_id'] 取代 auth()->id()
// updateOrCreate 查詢條件加入 user_id
public function callback(Request $request): RedirectResponse
{
    // ... 前面的驗證保持不變 ...

    $resolved = OAuthState::resolve($rawState);

    if ($resolved === null) {
        return $this->fail('OAuth state 無效或已過期，請重新授權');
    }

    $userId = $resolved['user_id'];
    $targetAccount = $resolved['account'];

    // ... code 驗證保持不變 ...

    try {
        $shortToken = $this->threads->exchangeCodeForShortToken($code);
        $longToken = $this->threads->exchangeShortForLongToken($shortToken);
        $profile = $this->threads->getProfile($longToken['access_token']);

        $attributes = [
            'user_id' => $userId,
            'username' => $profile['username'] ?? $profile['id'],
            'name' => $profile['name'] ?? null,
            'avatar' => null,
            'access_token' => $longToken['access_token'],
            'token_expires_at' => now()->addSeconds($longToken['expires_in'] ?? 5184000),
            'status' => ThreadsAccountStatus::Active,
        ];

        if ($targetAccount !== null) {
            $targetAccount->update($attributes);
            $account = $targetAccount;
            $message = "已重新授權帳號 @{$account->username}";
        } else {
            $account = ThreadsAccount::query()->updateOrCreate(
                [
                    'threads_user_id' => $profile['id'],
                    'user_id' => $userId,
                ],
                $attributes,
            );
            $message = "已成功綁定帳號 @{$account->username}";
        }
        // ... 後續 redirect 保持不變 ...
    }
}
```

同時移除 `use App\Models\ThreadsApp;` import。

- [ ] **步驟 3：修改 OAuth 路由**

修改 `routes/web.php`：

```php
Route::prefix('threads/oauth')->group(function () {
    Route::get('redirect', [ThreadsOAuthController::class, 'redirect'])->name('threads.oauth.redirect');
    Route::get('callback', [ThreadsOAuthController::class, 'callback'])->name('threads.oauth.callback');
});
```

- [ ] **步驟 4：Commit**

```bash
git add app/Services/ThreadsClient.php app/Http/Controllers/ThreadsOAuthController.php routes/web.php
git commit -m "refactor: ThreadsClient reads credentials from config, OAuth state carries user_id"
```

---

### 任務 5：新增 PostStatus 刪除狀態與 ThreadsClient::deleteMedia()

**檔案：**
- 修改：`app/Enums/PostStatus.php`
- 修改：`app/Services/ThreadsClient.php`

- [ ] **步驟 1：擴充 PostStatus 枚舉**

修改 `app/Enums/PostStatus.php`，在 `Failed` 之後新增：

```php
case Deleting = 'deleting';
case DeleteFailed = 'delete_failed';
```

並在 `getLabel()` 中新增：

```php
self::Deleting => '刪除中',
self::DeleteFailed => '刪除失敗',
```

在 `getColor()` 中新增：

```php
self::Deleting => 'warning',
self::DeleteFailed => 'danger',
```

- [ ] **步驟 2：在 ThreadsClient 新增 deleteMedia() 方法**

修改 `app/Services/ThreadsClient.php`，新增方法：

```php
/**
 * Delete a Threads media object.
 *
 * @see https://developers.facebook.com/docs/threads/reference/publishing#delete-a-threads-media-object
 */
public function deleteMedia(ThreadsAccount $account, string $mediaId): bool
{
    $this->request('DELETE', "/{$mediaId}", [
        'access_token' => $account->access_token,
    ]);

    return true;
}
```

注意：`request()` 方法目前只處理 GET/POST（`$options = $method === 'GET' ? ['query' => $params] : ['form_params' => $params]`），DELETE 請求需要調整 `request()` 方法以支援 DELETE：

```php
private function request(string $method, string $path, array $params, string $base = self::API_BASE): array
{
    $options = match ($method) {
        'GET' => ['query' => $params],
        'DELETE' => ['query' => $params],
        default => ['form_params' => $params],
    };

    try {
        $response = $this->http->request($method, $base.$path, $options);
    } catch (ClientException $e) {
        throw $this->toApiException($e);
    } catch (GuzzleException $e) {
        throw new ThreadsApiException($e->getMessage(), null, null);
    }

    return $this->decode($response);
}
```

- [ ] **步驟 3：Commit**

```bash
git add app/Enums/PostStatus.php app/Services/ThreadsClient.php
git commit -m "feat: add Deleting/DeleteFailed statuses and ThreadsClient::deleteMedia()"
```

---

### 任務 6：建立 DeletePost Job

**檔案：**
- 建立：`app/Jobs/DeletePost.php`

- [ ] **步驟 1：建立 Job 檔案**

```bash
php artisan make:job DeletePost --no-interaction
```

- [ ] **步驟 2：編寫 DeletePost Job**

```php
<?php

namespace App\Jobs;

use App\Enums\PostStatus;
use App\Enums\ThreadsAccountStatus;
use App\Exceptions\ThreadsApiException;
use App\Models\Post;
use App\Services\ThreadsClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class DeletePost implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly int $postId,
    ) {}

    public function handle(ThreadsClient $threads): void
    {
        $post = Post::query()->find($this->postId);

        if ($post === null || $post->status !== PostStatus::Deleting) {
            return;
        }

        $account = $post->threadsAccount;

        if ($account === null || $post->threads_media_id === null) {
            return;
        }

        try {
            $threads->deleteMedia($account, $post->threads_media_id);

            // 成功：刪除本地記錄（cascade 刪除關聯的 Reply）
            $post->delete();

            Log::info('Threads post deleted successfully', [
                'post_id' => $this->postId,
                'threads_media_id' => $post->threads_media_id,
            ]);
        } catch (ThreadsApiException $e) {
            if ($e->isTokenInvalid()) {
                $account->update(['status' => ThreadsAccountStatus::NeedsReauth]);
                $post->update([
                    'status' => PostStatus::DeleteFailed,
                    'error_message' => 'token 失效，請重新授權後再次嘗試刪除',
                ]);
            } else {
                $post->update([
                    'status' => PostStatus::DeleteFailed,
                    'error_message' => $e->getMessage(),
                ]);
            }

            Log::warning('Threads post deletion failed', [
                'post_id' => $this->postId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
```

- [ ] **步驟 3：Commit**

```bash
git add app/Jobs/DeletePost.php
git commit -m "feat: add DeletePost job for async Threads post deletion"
```

---

### 任務 7：PostService 新增 delete() 方法與 Filament 刪除動作調整

**檔案：**
- 修改：`app/Services/PostService.php`
- 修改：`app/Filament/Resources/Posts/Tables/PostsTable.php`

- [ ] **步驟 1：PostService 新增 delete() 方法**

修改 `app/Services/PostService.php`，新增方法與 import：

```php
use App\Jobs\DeletePost;

// 在 find() 方法之後新增：
/**
 * 觸發刪除貼文流程。
 * - Published / DeleteFailed：設為 Deleting → dispatch DeletePost job
 * - 其他狀態：直接刪除本地記錄
 */
public function delete(int $id, ?int $userId = null): void
{
    $userId ??= auth()->id();

    $post = Post::query()->where('user_id', $userId)->find($id);

    if ($post === null) {
        throw new InvalidArgumentException('貼文不存在或無權存取');
    }

    if (in_array($post->status, [PostStatus::Published, PostStatus::DeleteFailed], true)) {
        $post->update(['status' => PostStatus::Deleting]);
        DeletePost::dispatch($post->id);
    } else {
        $post->delete();
    }
}
```

- [ ] **步驟 2：調整 PostsTable 的 DeleteAction 邏輯**

修改 `app/Filament/Resources/Posts/Tables/PostsTable.php`：

```php
use App\Services\PostService;

// 修改 DeleteAction：
DeleteAction::make()
    ->visible(fn ($record) => ! in_array($record->status, [PostStatus::Deleting]))
    ->action(function ($record) {
        app(PostService::class)->delete($record->id);
    }),
```

移除原有的 `EditAction` 旁的 `DeleteAction`，改為統一的 `DeleteAction`（不區分狀態顯示，僅隱藏 `Deleting` 狀態的刪除按鈕）。

- [ ] **步驟 3：Commit**

```bash
git add app/Services/PostService.php app/Filament/Resources/Posts/Tables/PostsTable.php
git commit -m "feat: add PostService::delete() and update Filament delete action for post deletion flow"
```

---

### 任務 8：MCP 調整

**檔案：**
- 修改：`routes/ai.php`
- 修改：`app/Mcp/Servers/ThreadsMcpServer.php`

- [ ] **步驟 1：移除 MCP 本地模式**

修改 `routes/ai.php`，刪除 `Mcp::local('threads', ThreadsMcpServer::class);` 該行：

```php
<?php

use App\Mcp\Servers\ThreadsMcpServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::oauthRoutes();

Mcp::web('/mcp/threads', ThreadsMcpServer::class)
    ->middleware('auth:api');
```

- [ ] **步驟 2：更新 ThreadsMcpServer 的 Instructions**

修改 `app/Mcp/Servers/ThreadsMcpServer.php` 的 `#[Instructions]`：

```php
#[Instructions('此伺服器提供 Threads 帳號查詢、排程貼文與回覆功能。使用前請先在後台介面綁定 Threads 帳號。帳號僅供讀取，不提供新增／修改／刪除。')]
```

- [ ] **步驟 3：Commit**

```bash
git add routes/ai.php app/Mcp/Servers/ThreadsMcpServer.php
git commit -m "refactor: remove MCP local mode, update server instructions"
```

---

### 任務 9：文件更新

**檔案：**
- 修改：`README.md`
- 修改：`resources/views/filament/pages/usage-guide/chapter2.blade.php`
- 修改：`resources/views/filament/pages/usage-guide/chapter3.blade.php`
- 修改：`resources/views/filament/pages/usage-guide/chapter5.blade.php`

- [ ] **步驟 1：更新 README.md**

修改 `README.md`：
- 將「多 App 管理」章節改為「環境變數設定」，說明 `THREADS_CLIENT_ID` 與 `THREADS_CLIENT_SECRET` 的設定方式
- 移除「新增 Threads App」步驟，改為「設定環境變數」
- 更新 OAuth 流程說明（移除 App 相關描述）
- 更新架構圖（移除 ThreadsApp 層級）

- [ ] **步驟 2：更新 chapter2.blade.php（使用說明第二章）**

修改 `resources/views/filament/pages/usage-guide/chapter2.blade.php`：
- 將「新增 Threads App」步驟改為「設定環境變數」
- 說明在 `.env` 中設定 `THREADS_CLIENT_ID` 與 `THREADS_CLIENT_SECRET`
- 綁定帳號步驟簡化（不再需要選擇 App）

- [ ] **步驟 3：更新 chapter3.blade.php（使用說明第三章）**

修改 `resources/views/filament/pages/usage-guide/chapter3.blade.php`：
- 在狀態流程圖中補上 `Deleting`（刪除中）與 `DeleteFailed`（刪除失敗）狀態
- 新增刪除貼文說明區塊

- [ ] **步驟 4：更新 chapter5.blade.php（使用說明第五章）**

修改 `resources/views/filament/pages/usage-guide/chapter5.blade.php`：
- 移除「本地模式（開發者）」區塊
- 移除兩種模式對比的 grid 佈局
- 保留 HTTP 模式設定步驟

- [ ] **步驟 5：Commit**

```bash
git add README.md resources/views/filament/pages/usage-guide/
git commit -m "docs: update README and usage guide for ThreadsApp removal, post deletion, and MCP changes"
```

---

### 任務 10：測試更新

**檔案：**
- 刪除/修改：`tests/Feature/ThreadsAppResourceTest.php`
- 修改：`tests/Unit/OAuthStateTest.php`
- 修改：`tests/Unit/ThreadsClientTest.php`
- 建立：`tests/Feature/DeletePostTest.php`

- [ ] **步驟 1：處理 ThreadsAppResourceTest**

```bash
# 因為 ThreadsAppResource 已刪除，此測試檔案不再適用
rm tests/Feature/ThreadsAppResourceTest.php
```

- [ ] **步驟 2：更新 OAuthStateTest**

修改 `tests/Unit/OAuthStateTest.php`：

```php
<?php

namespace Tests\Unit;

use App\Models\OAuthState;
use App\Models\ThreadsAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OAuthStateTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_create_for_user_stores_hash_and_returns_raw_token(): void
    {
        $token = OAuthState::createForUser();

        $this->assertIsString($token);
        $this->assertNotSame('', $token);
        $this->assertDatabaseMissing('threads_oauth_states', ['token_hash' => $token]);

        $this->assertDatabaseHas('threads_oauth_states', [
            'token_hash' => hash('sha256', $token),
            'user_id' => $this->user->id,
        ]);
    }

    public function test_resolve_returns_user_id_and_consumes_state(): void
    {
        $token = OAuthState::createForUser();

        $resolved = OAuthState::resolve($token);

        $this->assertNotNull($resolved);
        $this->assertSame($this->user->id, $resolved['user_id']);
        $this->assertNull($resolved['account']);

        // 單次使用：解析後即刪除。
        $this->assertNull(OAuthState::resolve($token));
    }

    public function test_resolve_with_target_account_returns_account(): void
    {
        $account = ThreadsAccount::factory()->create(['user_id' => $this->user->id]);
        $token = OAuthState::createForUser($account);

        $resolved = OAuthState::resolve($token);

        $this->assertNotNull($resolved);
        $this->assertSame($account->id, $resolved['account']->id);
    }

    public function test_resolve_invalid_token_returns_null(): void
    {
        $this->assertNull(OAuthState::resolve('nonexistent-token'));
    }

    public function test_resolve_expired_token_returns_null(): void
    {
        $token = OAuthState::createForUser();

        OAuthState::query()->update(['expires_at' => now()->subMinute()]);

        $this->assertNull(OAuthState::resolve($token));
    }
}
```

- [ ] **步驟 3：更新 ThreadsClientTest**

修改 `tests/Unit/ThreadsClientTest.php`：

```php
// 1. 移除 use App\Models\ThreadsApp; import
// 2. 修改 test_exchange_code_for_short_token_returns_token：
//    - 刪除 ThreadsApp::factory()->create(...) 
//    - 改為在 setUp 中設定 config: Config::set('services.threads.client_id', 'test-client-id')
//      Config::set('services.threads.client_secret', 'test-client-secret')
//    - 呼叫改為 $this->client->exchangeCodeForShortToken('code')
// 3. 修改 test_exchange_short_for_long_token_returns_token_and_expiry：
//    - 同樣移除 ThreadsApp，改用 config
//    - 呼叫改為 $this->client->exchangeShortForLongToken('short-token')
// 4. 新增 test_delete_media_returns_true：
//    - 建立 ThreadsAccount
//    - mock HTTP DELETE 回傳 200
//    - 呼叫 $this->client->deleteMedia($account, 'media-id')
//    - assertTrue
```

具體新增的測試方法：

```php
public function test_delete_media_returns_true(): void
{
    $account = ThreadsAccount::factory()->create();

    $this->http->shouldReceive('request')
        ->once()
        ->with('DELETE', 'https://graph.threads.net/v1.0/media-id', Mockery::any())
        ->andReturn(new Response(200, [], json_encode(['success' => true])));

    $result = $this->client->deleteMedia($account, 'media-id');

    $this->assertTrue($result);
}
```

- [ ] **步驟 4：建立 DeletePostTest**

建立 `tests/Feature/DeletePostTest.php`：

```php
<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Enums\ThreadsAccountStatus;
use App\Jobs\DeletePost;
use App\Models\Post;
use App\Models\Reply;
use App\Models\ThreadsAccount;
use App\Models\User;
use App\Services\PostService;
use App\Services\ThreadsClient;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class DeletePostTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private ThreadsAccount $account;
    private PostService $postService;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.threads.client_id', 'test-client-id');
        Config::set('services.threads.client_secret', 'test-client-secret');

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->account = ThreadsAccount::factory()->create(['user_id' => $this->user->id]);
        $this->postService = app(PostService::class);
    }

    public function test_delete_published_post_dispatches_job_and_sets_deleting(): void
    {
        Queue::fake();

        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'threads_account_id' => $this->account->id,
            'status' => PostStatus::Published,
            'threads_media_id' => 'test-media-id',
        ]);

        $this->postService->delete($post->id);

        $post->refresh();
        $this->assertSame(PostStatus::Deleting, $post->status);

        Queue::assertPushed(DeletePost::class, fn ($job) => $job->postId === $post->id);
    }

    public function test_delete_delete_failed_post_dispatches_job_again(): void
    {
        Queue::fake();

        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'threads_account_id' => $this->account->id,
            'status' => PostStatus::DeleteFailed,
            'threads_media_id' => 'test-media-id',
            'error_message' => 'previous error',
        ]);

        $this->postService->delete($post->id);

        $post->refresh();
        $this->assertSame(PostStatus::Deleting, $post->status);

        Queue::assertPushed(DeletePost::class);
    }

    public function test_delete_draft_post_deletes_immediately(): void
    {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'threads_account_id' => $this->account->id,
            'status' => PostStatus::Draft,
        ]);

        $this->postService->delete($post->id);

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_delete_job_success_removes_post_and_cascade_replies(): void
    {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'threads_account_id' => $this->account->id,
            'status' => PostStatus::Deleting,
            'threads_media_id' => 'test-media-id',
        ]);

        $reply = Reply::factory()->create([
            'user_id' => $this->user->id,
            'threads_account_id' => $this->account->id,
            'post_id' => $post->id,
        ]);

        $http = Mockery::mock(\GuzzleHttp\ClientInterface::class);
        $http->shouldReceive('request')
            ->once()
            ->andReturn(new Response(200, [], json_encode(['success' => true])));

        $client = new ThreadsClient($http);
        $job = new DeletePost($post->id);
        $job->handle($client);

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
        $this->assertDatabaseMissing('replies', ['id' => $reply->id]);
    }

    public function test_delete_job_failure_sets_delete_failed_and_preserves_record(): void
    {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'threads_account_id' => $this->account->id,
            'status' => PostStatus::Deleting,
            'threads_media_id' => 'test-media-id',
        ]);

        $http = Mockery::mock(\GuzzleHttp\ClientInterface::class);
        $http->shouldReceive('request')
            ->once()
            ->andReturn(new Response(400, [], json_encode([
                'error' => ['message' => 'Some error', 'code' => 100],
            ])));

        $client = new ThreadsClient($http);
        $job = new DeletePost($post->id);
        $job->handle($client);

        $post->refresh();
        $this->assertSame(PostStatus::DeleteFailed, $post->status);
        $this->assertNotNull($post->error_message);
        $this->assertDatabaseHas('posts', ['id' => $post->id]);
    }

    public function test_delete_job_token_invalid_sets_account_needs_reauth(): void
    {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'threads_account_id' => $this->account->id,
            'status' => PostStatus::Deleting,
            'threads_media_id' => 'test-media-id',
        ]);

        $request = new \GuzzleHttp\Psr7\Request('DELETE', 'https://graph.threads.net/v1.0/test-media-id');
        $response = new Response(401, [], json_encode([
            'error' => ['message' => 'Invalid OAuth access token', 'code' => 190],
        ]));

        $http = Mockery::mock(\GuzzleHttp\ClientInterface::class);
        $http->shouldReceive('request')
            ->once()
            ->andThrow(new \GuzzleHttp\Exception\ClientException('Client error', $request, $response));

        $client = new ThreadsClient($http);
        $job = new DeletePost($post->id);
        $job->handle($client);

        $post->refresh();
        $this->assertSame(PostStatus::DeleteFailed, $post->status);

        $this->account->refresh();
        $this->assertSame(ThreadsAccountStatus::NeedsReauth, $this->account->status);
    }

    public function test_cannot_delete_other_users_post(): void
    {
        $otherUser = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $otherUser->id,
            'threads_account_id' => $this->account->id,
            'status' => PostStatus::Published,
            'threads_media_id' => 'test-media-id',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->postService->delete($post->id);
    }
}
```

- [ ] **步驟 5：執行測試確認全部通過**

```bash
php artisan test --compact --filter=OAuthStateTest
php artisan test --compact --filter=ThreadsClientTest
php artisan test --compact --filter=DeletePostTest
```

- [ ] **步驟 6：執行 Pint 格式化**

```bash
vendor/bin/pint --format agent
```

- [ ] **步驟 7：執行完整測試套件**

```bash
php artisan test --compact
```

- [ ] **步驟 8：Commit**

```bash
git add tests/
git commit -m "test: update tests for ThreadsApp removal, OAuth refactor, and post deletion"
```

---

### 任務 11：最終驗證與收尾

- [ ] **步驟 1：確認所有 migration 可正常執行**

```bash
php artisan migrate:fresh --no-interaction
```

- [ ] **步驟 2：確認 Filament 後台可正常存取**

```bash
php artisan serve
# 手動確認後台頁面無錯誤
```

- [ ] **步驟 3：最終 Commit（如有遺漏）**

```bash
git status
git add -A
git commit -m "chore: final cleanup and verification"
```
