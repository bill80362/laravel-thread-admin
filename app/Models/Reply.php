<?php

namespace App\Models;

use App\Enums\ReplySource;
use App\Enums\ReplyStatus;
use Database\Factories\ReplyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reply extends Model
{
    /** @use HasFactory<ReplyFactory> */
    use HasFactory;

    protected $fillable = [
        'threads_account_id',
        'post_id',
        'threads_reply_id',
        'author_username',
        'text',
        'source',
        'status',
        'replied_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'source' => ReplySource::class,
            'status' => ReplyStatus::class,
            'replied_at' => 'datetime',
        ];
    }

    /**
     * The Threads account this reply belongs to.
     *
     * @return BelongsTo<ThreadsAccount, Reply>
     */
    public function threadsAccount(): BelongsTo
    {
        return $this->belongsTo(ThreadsAccount::class);
    }

    /**
     * The post this reply belongs to, if any.
     *
     * @return BelongsTo<Post, Reply>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
