<?php

namespace App\Models;

use Database\Factories\ThreadsAppFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ThreadsApp extends Model
{
    /** @use HasFactory<ThreadsAppFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'client_id',
        'client_secret',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'client_secret' => 'encrypted',
        ];
    }

    /**
     * The user who manages this app.
     *
     * @return BelongsTo<User, ThreadsApp>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The Threads accounts bound to this app.
     *
     * @return HasMany<ThreadsAccount>
     */
    public function threadsAccounts(): HasMany
    {
        return $this->hasMany(ThreadsAccount::class);
    }
}
