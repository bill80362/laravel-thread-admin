<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostImage extends Model
{
    protected $fillable = [
        'post_id',
        'image_path',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    /**
     * The post this image belongs to.
     *
     * @return BelongsTo<Post, PostImage>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
