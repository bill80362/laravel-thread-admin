# Admin Panel 與圖片發文 實作計畫

> **面向 AI 代理的工作者：** 必需子技能：使用 superpowers:subagent-driven-development（推薦）或 superpowers:executing-plans 逐任務實現此計畫。步驟使用複選框（`- [ ]`）語法來追蹤進度。

**目標：** 引入 Admin/User 雙角色分離（雙 Model + 雙 Panel），新增圖片發文功能，更新 README

**架構：** 雙 Model（User + Admin 獨立表）對應雙 Filament Panel（`/user` + `/admin`）。User 新增控管欄位（max_accounts、max_daily_posts、max_daily_replies、is_active）。Post 新增 image_path 支援圖片發文。Admin 可管理 User CRUD、取消綁定且刪除、完整刪除 User。

**技術棧：** PHP 8.4 + Laravel 13 + Filament 5 + SQLite + Guzzle + Laravel Passport

> **⚠️ 關於 Filament Shield：** 專案已安裝 `bezhansalleh/filament-shield` 與 `spatie/laravel-permission`，但**目前完全未使用**（無 `HasRoles` trait、無 Shield Plugin 註冊、無 role/permission 程式碼）。本次變更**維持現狀**——不要啟用 Shield、不要在 Model 加 `HasRoles`、不要執行 `filament-shield:install`。僅保留套件供未來使用。

---

## 檔案結構

| 檔案 | 操作 | 職責 |
|------|------|------|
| `database/migrations/..._create_admins_table.php` | 建立 | admins 表 |
| `database/migrations/..._add_control_fields_to_users_table.php` | 建立 | users 加控管欄位 |
| `database/migrations/..._add_image_path_to_posts_table.php` | 建立 | posts 加 image_path，text 改 nullable |
| `database/migrations/..._add_cascade_to_threads_accounts_user_id.php` | 建立 | threads_accounts.user_id FK cascade |
| `app/Models/Admin.php` | 建立 | Admin 模型 |
| `database/factories/AdminFactory.php` | 建立 | Admin 工廠 |
| `app/Models/User.php` | 修改 | 加 fillable/casts |
| `app/Models/Post.php` | 修改 | 加 image_path fillable |
| `config/auth.php` | 修改 | 加 admin_web guard、admins provider |
| `app/Providers/Filament/AdminPanelProvider.php` | 修改→重命名 | → UserPanelProvider |
| `app/Providers/Filament/AdminPanelProvider.php` | 建立(新) | Admin Panel |
| `app/Console/Commands/MakeFilamentUser.php` | 修改 | 加控管欄位輸入 |
| `app/Console/Commands/MakeFilamentAdmin.php` | 建立 | 建立 Admin 命令 |
| `app/Filament/Resources/Users/UserResource.php` | 建立 | Admin 的 User 管理 |
| `app/Filament/Resources/Users/Schemas/UserForm.php` | 建立 | User 表單 |
| `app/Filament/Resources/Users/Tables/UsersTable.php` | 建立 | User 表格 |
| `app/Filament/Resources/Users/Pages/ListUsers.php` | 建立 | User 列表頁 |
| `app/Filament/Resources/Users/Pages/CreateUser.php` | 建立 | User 建立頁 |
| `app/Filament/Resources/Users/Pages/EditUser.php` | 建立 | User 編輯頁 |
| `app/Filament/Resources/Users/RelationManagers/ThreadsAccountsRelationManager.php` | 建立 | User 的帳號關聯管理 |
| `app/Filament/Widgets/AdminOverview.php` | 建立 | Admin Dashboard Widget |
| `app/Filament/Widgets/ThreadsOverview.php` | 修改 | 加 user_id scope |
| `app/Filament/Resources/Posts/Schemas/PostForm.php` | 修改 | 加 FileUpload、text nullable |
| `app/Filament/Resources/Posts/Tables/PostsTable.php` | 修改 | 加圖片欄位 |
| `app/Filament/Resources/Posts/Pages/CreatePost.php` | 修改 | 處理圖片上傳 |
| `app/Services/ThreadsClient.php` | 修改 | 加 createImageContainer() |
| `app/Services/PostService.php` | 修改 | 支援圖片 |
| `app/Jobs/PublishScheduledPost.php` | 修改 | 圖片/文字分支 |
| `app/Mcp/Tools/CreatePostTool.php` | 修改 | 加 image_url 參數 |
| `app/Filament/Pages/UsageGuide.php` | 修改 | 更新內容 |
| `resources/views/filament/pages/usage-guide.blade.php` | 修改 | 更新內容 |
| `README.md` | 修改 | 全面更新 |

---

### 任務 1：資料庫 Migration

**檔案：**
- 建立：`database/migrations/2026_08_19_000001_create_admins_table.php`
- 建立：`database/migrations/2026_08_19_000002_add_control_fields_to_users_table.php`
- 建立：`database/migrations/2026_08_19_000003_add_image_path_to_posts_table.php`
- 建立：`database/migrations/2026_08_19_000004_add_cascade_to_threads_accounts_user_id.php`

- [ ] **步驟 1：建立 admins 表 migration**

```bash
php artisan make:migration create_admins_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
```

- [ ] **步驟 2：建立 users 控管欄位 migration**

```bash
php artisan make:migration add_control_fields_to_users_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('max_accounts')->default(3)->after('password');
            $table->unsignedInteger('max_daily_posts')->default(10)->after('max_accounts');
            $table->unsignedInteger('max_daily_replies')->default(50)->after('max_daily_posts');
            $table->boolean('is_active')->default(true)->after('max_daily_replies');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['max_accounts', 'max_daily_posts', 'max_daily_replies', 'is_active']);
        });
    }
};
```

- [ ] **步驟 3：建立 posts 圖片欄位 migration**

```bash
php artisan make:migration add_image_path_to_posts_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('text');
        });

        // text 改為 nullable（純圖片時無文字）
        Schema::table('posts', function (Blueprint $table) {
            $table->string('text', 500)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->string('text', 500)->nullable(false)->change();
        });
    }
};
```

- [ ] **步驟 4：建立 threads_accounts.user_id cascade migration**

```bash
php artisan make:migration add_cascade_to_threads_accounts_user_id
```

先檢查現有 FK 名稱：
```bash
php artisan tinker --execute 'echo json_encode(array_keys(Schema::getForeignKeys("threads_accounts")), JSON_PRETTY_PRINT);'
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 先刪除舊 FK，再重建含 cascadeOnDelete
        Schema::table('threads_accounts', function (Blueprint $table) {
            // 找出實際 FK 名稱後替換
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('threads_accounts', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('users');
        });
    }
};
```

- [ ] **步驟 5：執行 migration 並驗證**

```bash
php artisan migrate
```

驗證：`php artisan tinker --execute 'echo json_encode(Schema::getColumns("admins"), JSON_PRETTY_PRINT);'`
驗證：`php artisan tinker --execute 'echo json_encode(Schema::getColumns("users"), JSON_PRETTY_PRINT);'`
驗證：`php artisan tinker --execute 'echo json_encode(Schema::getColumns("posts"), JSON_PRETTY_PRINT);'`

- [ ] **步驟 6：Commit**

```bash
git add database/migrations/
git commit -m "feat: 新增 admins 表、users 控管欄位、posts 圖片欄位、FK cascade"
```

---

### 任務 2：Model 層

**檔案：**
- 建立：`app/Models/Admin.php`
- 建立：`database/factories/AdminFactory.php`
- 修改：`app/Models/User.php`
- 修改：`app/Models/Post.php`

- [ ] **步驟 1：建立 Admin Model**

```php
<?php

namespace App\Models;

use Database\Factories\AdminFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Admin extends Authenticatable
{
    /** @use HasFactory<AdminFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }
}
```

- [ ] **步驟 2：建立 AdminFactory**

```php
<?php

namespace Database\Factories;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<Admin>
 */
class AdminFactory extends Factory
{
    protected $model = Admin::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }
}
```

- [ ] **步驟 3：修改 User Model**

在 `app/Models/User.php` 的 `#[Fillable]` 屬性中加入新欄位：

```php
#[Fillable(['name', 'email', 'password', 'max_accounts', 'max_daily_posts', 'max_daily_replies', 'is_active'])]
```

在 `casts()` 方法中加入：

```php
'is_active' => 'boolean',
```

- [ ] **步驟 4：修改 Post Model**

在 `app/Models/Post.php` 的 `$fillable` 中加入 `'image_path'`。

- [ ] **步驟 5：Commit**

```bash
git add app/Models/Admin.php app/Models/User.php app/Models/Post.php database/factories/AdminFactory.php
git commit -m "feat: 新增 Admin Model、更新 User/Post Model"
```

---

### 任務 3：Auth 設定

**檔案：**
- 修改：`config/auth.php`

- [ ] **步驟 1：修改 config/auth.php**

在 `guards` 陣列中加入 `admin_web`：

```php
'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],

    'admin_web' => [
        'driver' => 'session',
        'provider' => 'admins',
    ],

    'api' => [
        'driver' => 'passport',
        'provider' => 'users',
    ],
],
```

在 `providers` 陣列中加入 `admins`：

```php
'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => env('AUTH_MODEL', App\Models\User::class),
    ],

    'admins' => [
        'driver' => 'eloquent',
        'model' => App\Models\Admin::class,
    ],
],
```

- [ ] **步驟 2：Commit**

```bash
git add config/auth.php
git commit -m "feat: 新增 admin_web guard 與 admins provider"
```

---

### 任務 4：Filament Panel Providers

**檔案：**
- 修改→重命名：`app/Providers/Filament/AdminPanelProvider.php` → `UserPanelProvider.php`
- 建立：`app/Providers/Filament/AdminPanelProvider.php`（新）

- [ ] **步驟 1：重命名現有 Provider 為 UserPanelProvider**

將 `app/Providers/Filament/AdminPanelProvider.php` 的類別改為：

```php
<?php

namespace App\Providers\Filament;

use App\Filament\Pages\EditPassword;
use App\Filament\Pages\UsageGuide;
use App\Filament\Widgets\ThreadsOverview;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class UserPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('user')
            ->path('user')
            ->login()
            ->profile(EditPassword::class)
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                ThreadsOverview::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
```

- [ ] **步驟 2：建立新的 AdminPanelProvider**

```php
<?php

namespace App\Providers\Filament;

use App\Filament\Pages\EditPassword;
use App\Filament\Widgets\AdminOverview;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')
            ->login()
            ->profile(EditPassword::class)
            ->colors([
                'primary' => Color::Blue,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AdminOverview::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authGuard('admin_web')
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
```

- [ ] **步驟 3：驗證 Panel 註冊**

```bash
php artisan filament:list
```

應看到兩個 panel：`user`（/user）和 `admin`（/admin）

- [ ] **步驟 4：Commit**

```bash
git add app/Providers/Filament/
git commit -m "feat: 拆分 UserPanelProvider 與 AdminPanelProvider"
```

---

### 任務 5：Artisan 命令

**檔案：**
- 修改：`app/Console/Commands/MakeFilamentUser.php`
- 建立：`app/Console/Commands/MakeFilamentAdmin.php`

- [ ] **步驟 1：修改 MakeFilamentUser 命令**

先讀取現有命令內容，然後修改 `handle()` 方法，在建立 User 時加入控管欄位：

```php
$user = User::create([
    'name' => $data['name'],
    'email' => $data['email'],
    'password' => Hash::make($data['password']),
    'max_accounts' => (int) ($data['max_accounts'] ?? 3),
    'max_daily_posts' => (int) ($data['max_daily_posts'] ?? 10),
    'max_daily_replies' => (int) ($data['max_daily_replies'] ?? 50),
    'is_active' => (bool) ($data['is_active'] ?? true),
]);
```

並在表單中加入對應的 `$this->ask()` 或使用預設值。

- [ ] **步驟 2：建立 MakeFilamentAdmin 命令**

```bash
php artisan make:command MakeFilamentAdmin
```

```php
<?php

namespace App\Console\Commands;

use App\Models\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class MakeFilamentAdmin extends Command
{
    protected $signature = 'make:filament-admin';

    protected $description = '建立 Filament 管理員帳號';

    public function handle(): int
    {
        $data['name'] = text(
            label: 'Name',
            required: true,
        );

        $data['email'] = text(
            label: 'Email',
            required: true,
            validate: fn (string $email): ?string => match (true) {
                Admin::where('email', $email)->exists() => '此 Email 已被使用',
                default => null,
            },
        );

        $data['password'] = password(
            label: 'Password',
            required: true,
            validate: fn (string $value): ?string => match (true) {
                strlen($value) < 8 => '密碼至少需要 8 個字元',
                default => null,
            },
        );

        Admin::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $this->info("管理員 [{$data['email']}] 建立成功！");

        return self::SUCCESS;
    }
}
```

- [ ] **步驟 3：驗證命令**

```bash
php artisan make:filament-admin
```

- [ ] **步驟 4：Commit**

```bash
git add app/Console/Commands/
git commit -m "feat: 修改 make:filament-user 加入控管欄位，新增 make:filament-admin"
```

---

### 任務 6：Admin Panel Resources（UserResource）

**檔案：**
- 建立：`app/Filament/Resources/Users/UserResource.php`
- 建立：`app/Filament/Resources/Users/Schemas/UserForm.php`
- 建立：`app/Filament/Resources/Users/Tables/UsersTable.php`
- 建立：`app/Filament/Resources/Users/Pages/ListUsers.php`
- 建立：`app/Filament/Resources/Users/Pages/CreateUser.php`
- 建立：`app/Filament/Resources/Users/Pages/EditUser.php`
- 建立：`app/Filament/Resources/Users/RelationManagers/ThreadsAccountsRelationManager.php`
- 建立：`app/Filament/Widgets/AdminOverview.php`

- [ ] **步驟 1：建立 UserResource**

```bash
php artisan make:filament-resource User --no-interaction
```

然後修改 `app/Filament/Resources/UserResource.php`（移動到 `Users/` 目錄下）：

```php
<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $navigationLabel = '使用者管理';

    protected static ?string $modelLabel = '使用者';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ThreadsAccountsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
```

- [ ] **步驟 2：建立 UserForm**

```php
<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Operation;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('基本資訊')
                    ->schema([
                        TextInput::make('name')
                            ->label('名稱')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        TextInput::make('new_password')
                            ->label('新密碼')
                            ->password()
                            ->nullable()
                            ->minLength(8)
                            ->dehydrateStateUsing(fn (?string $state): ?string => $state ? Hash::make($state) : null)
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->required(fn (Operation $operation): bool => $operation === Operation::Create)
                            ->visible(fn (Operation $operation): bool => $operation === Operation::Create),

                        TextInput::make('new_password')
                            ->label('新密碼（留空不修改）')
                            ->password()
                            ->nullable()
                            ->minLength(8)
                            ->dehydrateStateUsing(fn (?string $state): ?string => $state ? Hash::make($state) : null)
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->visible(fn (Operation $operation): bool => $operation === Operation::Edit),
                    ])
                    ->columns(2),

                Section::make('控管設定')
                    ->schema([
                        TextInput::make('max_accounts')
                            ->label('最大綁定帳號數')
                            ->integer()
                            ->required()
                            ->default(3)
                            ->minValue(0),

                        TextInput::make('max_daily_posts')
                            ->label('每日發文上限')
                            ->integer()
                            ->required()
                            ->default(10)
                            ->minValue(0),

                        TextInput::make('max_daily_replies')
                            ->label('每日回覆上限')
                            ->integer()
                            ->required()
                            ->default(50)
                            ->minValue(0),

                        Toggle::make('is_active')
                            ->label('啟用')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }
}
```

- [ ] **步驟 3：建立 UsersTable**

```php
<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\Post;
use App\Models\Reply;
use App\Models\ThreadsAccount;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('名稱')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('account_usage')
                    ->label('帳號數')
                    ->state(fn (User $record): string => sprintf(
                        '%d/%d',
                        ThreadsAccount::where('user_id', $record->id)->count(),
                        $record->max_accounts,
                    ))
                    ->sortable(query: fn ($query, $direction) => $query->orderBy('max_accounts', $direction)),

                TextColumn::make('daily_post_usage')
                    ->label('今日發文')
                    ->state(fn (User $record): string => sprintf(
                        '%d/%d',
                        Post::where('user_id', $record->id)->whereDate('created_at', today())->count(),
                        $record->max_daily_posts,
                    )),

                TextColumn::make('daily_reply_usage')
                    ->label('今日回覆')
                    ->state(fn (User $record): string => sprintf(
                        '%d/%d',
                        Reply::where('user_id', $record->id)->whereDate('created_at', today())->count(),
                        $record->max_daily_replies,
                    )),

                IconColumn::make('is_active')
                    ->label('啟用')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('建立時間')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                DeleteAction::make()
                    ->label('刪除')
                    ->modalHeading('刪除使用者')
                    ->modalDescription('確定要刪除此使用者嗎？該使用者下的所有帳號、貼文、回覆與 MCP Token 將一併刪除，此操作無法復原。注意：不會刪除 Threads 上的實際貼文。')
                    ->before(function (User $record) {
                        // 刪除 Passport Token
                        $record->tokens()->delete();
                    }),
            ]);
    }
}
```

- [ ] **步驟 4：建立 ListUsers / CreateUser / EditUser Pages**

```bash
php artisan make:filament-page ListUsers --resource=UserResource --no-interaction
php artisan make:filament-page CreateUser --resource=UserResource --no-interaction
php artisan make:filament-page EditUser --resource=UserResource --no-interaction
```

將產生的檔案移至 `app/Filament/Resources/Users/Pages/`，並修正 namespace 為 `App\Filament\Resources\Users\Pages`。

`CreateUser` 需處理密碼欄位映射：

```php
protected function mutateFormDataBeforeCreate(array $data): array
{
    if (isset($data['new_password'])) {
        $data['password'] = $data['new_password'];
        unset($data['new_password']);
    }
    return $data;
}
```

`EditUser` 需處理密碼欄位映射：

```php
protected function mutateFormDataBeforeSave(array $data): array
{
    if (isset($data['new_password']) && filled($data['new_password'])) {
        $data['password'] = $data['new_password'];
    }
    unset($data['new_password']);
    return $data;
}
```

- [ ] **步驟 5：建立 ThreadsAccountsRelationManager**

```php
<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Enums\ThreadsAccountStatus;
use App\Models\ThreadsAccount;
use Filament\Actions\DeleteAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ThreadsAccountsRelationManager extends RelationManager
{
    protected static string $relationship = 'threadsAccounts';

    protected static ?string $title = 'Threads 帳號';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('username')
                    ->label('帳號')
                    ->searchable(),

                TextColumn::make('name')
                    ->label('名稱'),

                TextColumn::make('status')
                    ->label('狀態')
                    ->badge()
                    ->color(fn (ThreadsAccountStatus $state): string => match ($state) {
                        ThreadsAccountStatus::Active => 'success',
                        ThreadsAccountStatus::NeedsReauth => 'danger',
                        ThreadsAccountStatus::Disabled => 'gray',
                    })
                    ->formatStateUsing(fn (ThreadsAccountStatus $state): string => match ($state) {
                        ThreadsAccountStatus::Active => '已綁定',
                        ThreadsAccountStatus::NeedsReauth => '需重新授權',
                        ThreadsAccountStatus::Disabled => '已停用',
                    }),

                TextColumn::make('token_expires_at')
                    ->label('Token 到期日')
                    ->dateTime('Y-m-d H:i'),
            ])
            ->actions([
                DeleteAction::make()
                    ->label('取消綁定且刪除')
                    ->modalHeading('取消綁定且刪除 Threads 帳號')
                    ->modalDescription('確定要取消綁定且刪除嗎？該帳號下的所有貼文與回覆記錄將一併刪除，此操作無法復原。注意：不會刪除 Threads 上的實際貼文。'),
            ]);
    }
}
```

- [ ] **步驟 6：建立 AdminOverview Widget**

```php
<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('使用者總數', User::query()->count())
                ->description('所有使用者')
                ->color('primary'),

            Stat::make('啟用中', User::query()->where('is_active', true)->count())
                ->description('已啟用的使用者')
                ->color('success'),

            Stat::make('已停用', User::query()->where('is_active', false)->count())
                ->description('已停用的使用者')
                ->color('danger'),
        ];
    }
}
```

- [ ] **步驟 7：Commit**

```bash
git add app/Filament/Resources/Users/ app/Filament/Widgets/AdminOverview.php
git commit -m "feat: 新增 Admin UserResource、ThreadsAccountsRelationManager、AdminOverview"
```

---

### 任務 7：User Panel Resources 調整

**檔案：**
- 修改：`app/Filament/Widgets/ThreadsOverview.php`

- [ ] **步驟 1：ThreadsOverview 加上 user_id scope**

```php
Stat::make('已綁定帳號', ThreadsAccount::query()->where('user_id', auth()->id())->count())
    ->description('Threads 帳號總數')
    ->color('success'),

Stat::make('待回覆留言', Reply::query()->where('user_id', auth()->id())->where('status', ReplyStatus::New)->count())
    ->description('尚未處理的回覆')
    ->color('danger'),

Stat::make('需重新授權', ThreadsAccount::query()->where('user_id', auth()->id())->where('status', ThreadsAccountStatus::NeedsReauth)->count())
    ->description('token 失效的帳號')
    ->color('warning'),
```

- [ ] **步驟 2：Commit**

```bash
git add app/Filament/Widgets/ThreadsOverview.php
git commit -m "fix: ThreadsOverview 加上 user_id scope"
```

---

### 任務 8：圖片發文功能

**檔案：**
- 修改：`app/Services/ThreadsClient.php`
- 修改：`app/Services/PostService.php`
- 修改：`app/Jobs/PublishScheduledPost.php`
- 修改：`app/Filament/Resources/Posts/Schemas/PostForm.php`
- 修改：`app/Filament/Resources/Posts/Tables/PostsTable.php`
- 修改：`app/Filament/Resources/Posts/Pages/CreatePost.php`

- [ ] **步驟 1：ThreadsClient 新增 createImageContainer()**

在 `app/Services/ThreadsClient.php` 的 `createTextContainer()` 方法後新增：

```php
/**
 * Create an image media container for a post.
 *
 * @param  string|null  $text  optional caption text
 */
public function createImageContainer(ThreadsAccount $account, string $imageUrl, ?string $text = null): string
{
    $params = [
        'media_type' => 'IMAGE',
        'image_url' => $imageUrl,
        'access_token' => $account->access_token,
    ];

    if ($text !== null && $text !== '') {
        $params['text'] = $text;
    }

    $data = $this->request('POST', "/{$account->threads_user_id}/threads", $params);

    return $data['id'];
}
```

- [ ] **步驟 2：PostService::create() 支援圖片**

修改 `create()` 方法，加入 `image` 處理：

```php
public function create(array $data): Post
{
    $userId = auth()->id();

    $account = ThreadsAccount::query()
        ->where('user_id', $userId)
        ->find($data['threads_account_id']);

    if ($account === null) {
        throw new InvalidArgumentException('帳號不存在或無權存取');
    }

    // 驗證至少要有 text 或 image
    if (empty($data['text']) && empty($data['image'])) {
        throw new InvalidArgumentException('貼文內容或圖片至少需填寫一項');
    }

    $post = new Post;
    $post->user_id = $userId;
    $post->threads_account_id = $data['threads_account_id'];
    $post->text = $data['text'] ?? null;
    $post->scheduled_at = $data['scheduled_at'];
    $post->status = PostStatus::Scheduled;

    // 處理圖片上傳
    if (! empty($data['image'])) {
        $post->image_path = $data['image'];
    }

    $post->save();

    return $post;
}
```

- [ ] **步驟 3：PublishScheduledPost 支援圖片發佈 + is_active 檢查**

修改 `handle()` 方法，在開頭加入 `is_active` 檢查，並調整 container 建立邏輯：

```php
public function handle(ThreadsClient $threads): void
{
    $post = Post::query()->find($this->postId);

    $expectedStatus = $this->creationId === null
        ? PostStatus::Scheduled
        : PostStatus::Publishing;

    if ($post === null || $post->status !== $expectedStatus) {
        return;
    }

    $account = $post->threadsAccount;

    if ($account === null) {
        return;
    }

    // 停用的使用者：排程貼文不發佈
    if ($post->user !== null && ! $post->user->is_active) {
        return;
    }

    try {
        if ($this->creationId === null) {
            if ($post->image_path !== null) {
                $imageUrl = Storage::disk('public')->url($post->image_path);
                $creationId = $threads->createImageContainer($account, $imageUrl, $post->text);
            } else {
                $creationId = $threads->createTextContainer($account, $post->text);
            }
            $post->update(['status' => PostStatus::Publishing]);

            static::dispatch($this->postId, $creationId)
                ->delay(now()->addSeconds(self::PUBLISH_DELAY_SECONDS));

            return;
        }
        // ... 其餘 publishContainer 邏輯不變
    }
    // ...
}
```

需在檔案頂部加入 `use Illuminate\Support\Facades\Storage;`。

- [ ] **步驟 4：PostForm 新增圖片上傳欄位**

在 `app/Filament/Resources/Posts/Schemas/PostForm.php` 的 `configure()` 方法中，在 `Textarea::make('text')` 之前加入：

```php
FileUpload::make('image')
    ->label('圖片')
    ->image()
    ->disk('public')
    ->directory('posts')
    ->acceptedFileTypes(['image/jpeg', 'image/png'])
    ->maxSize(8192)
    ->helperText('支援 JPEG、PNG，最大 8MB。文字與圖片至少需填寫一項。'),
```

並將 `Textarea::make('text')` 的 `->required()` 改為 `->nullable()`，helperText 改為 `'最多 500 字元。文字與圖片至少需填寫一項。'`。

需在檔案頂部加入 `use Filament\Forms\Components\FileUpload;`。

- [ ] **步驟 5：PostsTable 新增圖片欄位**

在 `app/Filament/Resources/Posts/Tables/PostsTable.php` 的 columns 中加入：

```php
ImageColumn::make('image_path')
    ->label('圖片')
    ->disk('public')
    ->placeholder('無圖片'),
```

需在檔案頂部加入 `use Filament\Tables\Columns\ImageColumn;`。

- [ ] **步驟 6：CreatePost 處理圖片上傳**

在 `app/Filament/Resources/Posts/Pages/CreatePost.php` 中，確認 `mutateFormDataBeforeCreate()` 正確處理圖片路徑（Filament FileUpload 會自動處理上傳並回傳路徑）。

- [ ] **步驟 7：執行 storage:link**

```bash
php artisan storage:link
```

- [ ] **步驟 8：Commit**

```bash
git add app/Services/ThreadsClient.php app/Services/PostService.php app/Jobs/PublishScheduledPost.php app/Filament/Resources/Posts/
git commit -m "feat: 新增圖片發文功能（ThreadsClient、PostService、PublishScheduledPost、PostForm）"
```

---

### 任務 9：MCP Tools 更新

**檔案：**
- 修改：`app/Mcp/Tools/CreatePostTool.php`

- [ ] **步驟 1：CreatePostTool 新增 image_url 參數**

修改 `handle()` 方法：

```php
public function handle(Request $request, PostService $posts): Response|ResponseFactory
{
    $data = $request->validate([
        'threads_account_id' => ['required', 'integer', Rule::exists('threads_accounts', 'id')->where('user_id', auth()->id())],
        'text' => ['nullable', 'string', 'max:500'],
        'image_url' => ['nullable', 'string', 'url'],
        'scheduled_at' => ['required', 'date'],
    ]);

    // 驗證至少要有 text 或 image_url
    if (empty($data['text']) && empty($data['image_url'])) {
        return Response::error('貼文內容或圖片 URL 至少需填寫一項');
    }

    $post = $posts->create($data);

    return Response::structured([
        'post' => [
            'id' => $post->id,
            'threads_account_id' => $post->threads_account_id,
            'text' => $post->text,
            'image_path' => $post->image_path,
            'scheduled_at' => $post->scheduled_at,
            'status' => $post->status->value,
        ],
    ]);
}
```

修改 `schema()` 方法，加入 `image_url`：

```php
'image_url' => $schema->string()
    ->description('圖片公開 URL（選填，若有則發佈圖文貼文。客戶端需自行上傳圖片到公開 URL）'),
```

並將 `text` 改為非必填：

```php
'text' => $schema->string()
    ->description('貼文內容（最多 500 字元，與圖片至少需填寫一項）'),
```

- [ ] **步驟 2：PostService 支援 MCP 的 image_url**

修改 `PostService::create()` 以支援從 MCP 傳入的 `image_url`（直接寫入 `image_path`）：

```php
// 處理圖片（來自 Filament 上傳或 MCP image_url）
if (! empty($data['image'])) {
    $post->image_path = $data['image'];
} elseif (! empty($data['image_url'])) {
    $post->image_path = $data['image_url'];
}
```

- [ ] **步驟 3：Commit**

```bash
git add app/Mcp/Tools/CreatePostTool.php app/Services/PostService.php
git commit -m "feat: MCP CreatePostTool 新增 image_url 參數支援圖片發文"
```

---

### 任務 10：使用說明與 README 更新

**檔案：**
- 修改：`app/Filament/Pages/UsageGuide.php`
- 修改：`resources/views/filament/pages/usage-guide.blade.php`（及子視圖）
- 修改：`README.md`

- [ ] **步驟 1：更新 UsageGuide 頁面**

在 `UsageGuide.php` 中新增章節反映雙角色與圖片發文。

- [ ] **步驟 2：更新 README.md**

全面改寫 README.md，包含：
- User vs Admin 雙角色說明
- 登入 URL：`/user/login` 和 `/admin/login`
- 圖片發文功能說明
- 管理員功能說明（User CRUD、控管設定、取消綁定且刪除、完整刪除）
- 更新安裝步驟（`make:filament-user` + `make:filament-admin`）
- 更新操作說明

- [ ] **步驟 3：Commit**

```bash
git add README.md app/Filament/Pages/UsageGuide.php resources/views/filament/pages/usage-guide.blade.php
git commit -m "docs: 更新 README 與使用說明，反映雙角色與圖片發文功能"
```

---

### 任務 11：驗證與收尾

- [ ] **步驟 1：執行 migration 重置測試**

```bash
php artisan migrate:fresh
```

- [ ] **步驟 2：建立測試帳號**

```bash
php artisan make:filament-user
php artisan make:filament-admin
```

- [ ] **步驟 3：執行現有測試**

```bash
php artisan test --compact
```

確認所有現有測試通過。

- [ ] **步驟 4：執行程式碼格式化**

```bash
vendor/bin/pint --format agent
```

- [ ] **步驟 5：Commit**

```bash
git add .
git commit -m "chore: 程式碼格式化與最終驗證"
```
