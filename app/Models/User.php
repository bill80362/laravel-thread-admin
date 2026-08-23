<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'max_accounts', 'max_daily_posts', 'max_daily_replies', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * 刪除 User 時 cascade 刪除關聯資料（不使用 FK）。
     */
    protected static function booted(): void
    {
        static::deleting(function (User $user) {
            // 刪除所有 Threads 帳號（會觸發 ThreadsAccount 的 deleting 事件）
            $user->threadsAccounts->each(fn ($account) => $account->delete());
            // 刪除 Passport Token
            $user->tokens()->delete();
        });
    }

    /**
     * The Threads accounts owned by this user.
     */
    public function threadsAccounts(): HasMany
    {
        return $this->hasMany(ThreadsAccount::class);
    }

    /**
     * The activity logs for this user.
     *
     * @return HasMany<ActivityLog>
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }
}
