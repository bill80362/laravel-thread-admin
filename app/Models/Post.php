<?php

namespace App\Models;

use App\Enums\PostStatus;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory;

    protected $fillable = [
        'threads_account_id',
        'threads_media_id',
        'text',
        'scheduled_at',
        'published_at',
        'status',
        'publish_attempts',
        'error_message',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'published_at' => 'datetime',
            'status' => PostStatus::class,
        ];
    }

    /**
     * The Threads account this post belongs to.
     *
     * @return BelongsTo<ThreadsAccount, Post>
     */
    public function threadsAccount(): BelongsTo
    {
        return $this->belongsTo(ThreadsAccount::class);
    }

    /**
     * The replies collected for this post.
     *
     * @return HasMany<Reply>
     */
    public function replies(): HasMany
    {
        return $this->hasMany(Reply::class);
    }
}
