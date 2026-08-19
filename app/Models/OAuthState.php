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
     * Resolve a raw token to its user and optional target account.
     *
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

        // 單次使用：解析後立即刪除，防止重放。
        $state->delete();

        return [
            'user_id' => $state->user_id,
            'account' => $state->threadsAccount,
        ];
    }
}
