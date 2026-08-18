<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OAuthState extends Model
{
    protected $table = 'threads_oauth_states';

    protected $fillable = [
        'user_id',
        'token_hash',
        'threads_app_id',
        'threads_account_id',
        'expires_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    /**
     * The user who initiated this OAuth flow.
     *
     * @return BelongsTo<User, OAuthState>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The app that initiated this OAuth flow.
     *
     * @return BelongsTo<ThreadsApp, OAuthState>
     */
    public function threadsApp(): BelongsTo
    {
        return $this->belongsTo(ThreadsApp::class);
    }

    /**
     * The account being re-authorized, if any.
     *
     * @return BelongsTo<ThreadsAccount, OAuthState>
     */
    public function threadsAccount(): BelongsTo
    {
        return $this->belongsTo(ThreadsAccount::class);
    }

    /**
     * Create a state record for an OAuth flow and return the raw token.
     */
    public static function createForApp(ThreadsApp $app, ?ThreadsAccount $account = null): string
    {
        $token = bin2hex(random_bytes(32));

        self::query()->create([
            'token_hash' => hash('sha256', $token),
            'threads_app_id' => $app->id,
            'threads_account_id' => $account?->id,
            'user_id' => auth()->id(),
            'expires_at' => now()->addMinutes(10),
        ]);

        return $token;
    }

    /**
     * Resolve a raw token to its app and optional target account.
     *
     * @return array{app: ThreadsApp, account: ?ThreadsAccount}|null
     */
    public static function resolve(string $token): ?array
    {
        $state = self::query()
            ->where('token_hash', hash('sha256', $token))
            ->where('user_id', auth()->id())
            ->first();

        if ($state === null || $state->expires_at->isPast()) {
            return null;
        }

        // 單次使用：解析後立即刪除，防止重放。
        $state->delete();

        return [
            'app' => $state->threadsApp,
            'account' => $state->threadsAccount,
        ];
    }
}
