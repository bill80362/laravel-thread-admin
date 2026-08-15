<?php

namespace App\Models;

use App\Enums\ThreadsAccountStatus;
use Database\Factories\ThreadsAccountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ThreadsAccount extends Model
{
    /** @use HasFactory<ThreadsAccountFactory> */
    use HasFactory;

    protected $fillable = [
        'threads_app_id',
        'threads_user_id',
        'username',
        'name',
        'avatar',
        'access_token',
        'token_expires_at',
        'status',
        'last_synced_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'token_expires_at' => 'datetime',
            'last_synced_at' => 'datetime',
            'status' => ThreadsAccountStatus::class,
        ];
    }

    /**
     * The Threads app this account belongs to.
     *
     * @return BelongsTo<ThreadsApp, ThreadsAccount>
     */
    public function threadsApp(): BelongsTo
    {
        return $this->belongsTo(ThreadsApp::class);
    }

    /**
     * The posts scheduled for this account.
     *
     * @return HasMany<Post>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * The replies collected for this account.
     *
     * @return HasMany<Reply>
     */
    public function replies(): HasMany
    {
        return $this->hasMany(Reply::class);
    }
}
