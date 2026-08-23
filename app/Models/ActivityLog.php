<?php

namespace App\Models;

use Database\Factories\ActivityLogFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    /** @use HasFactory<ActivityLogFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'threads_account_id',
        'type',
        'reference_id',
        'threads_media_id',
        'text',
    ];

    /**
     * The user who performed this activity.
     *
     * @return BelongsTo<User, ActivityLog>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The Threads account used for this activity.
     *
     * @return BelongsTo<ThreadsAccount, ActivityLog>
     */
    public function threadsAccount(): BelongsTo
    {
        return $this->belongsTo(ThreadsAccount::class);
    }

    /**
     * 篩選今日的記錄。
     *
     * @param  Builder<ActivityLog>  $query
     * @return Builder<ActivityLog>
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * 計算某使用者今日特定類型的發送數量。
     */
    public static function countTodayForUser(int $userId, string $type): int
    {
        return static::query()
            ->where('user_id', $userId)
            ->where('type', $type)
            ->whereDate('created_at', today())
            ->count();
    }
}
