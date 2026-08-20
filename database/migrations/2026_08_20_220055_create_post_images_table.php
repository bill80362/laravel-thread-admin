<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. 建立 post_images 表
        Schema::create('post_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->string('image_path');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // 2. 遷移既有 posts.image_path 資料
        DB::statement('
            INSERT INTO post_images (post_id, image_path, sort_order, created_at, updated_at)
            SELECT id, image_path, 0, created_at, updated_at
            FROM posts
            WHERE image_path IS NOT NULL
        ');

        // 3. Drop posts.image_path 欄位
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 還原 image_path 欄位
        Schema::table('posts', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('text');
        });

        // 從 post_images 還原資料（取 sort_order=0 的首張圖）
        DB::statement('
            UPDATE posts SET image_path = (
                SELECT image_path FROM post_images
                WHERE post_images.post_id = posts.id
                ORDER BY sort_order ASC
                LIMIT 1
            )
        ');

        Schema::dropIfExists('post_images');
    }
};
