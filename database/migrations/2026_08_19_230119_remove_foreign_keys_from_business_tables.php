<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 移除 posts、replies、threads_accounts 的所有 FK。
     * SQLite 不支援 ALTER TABLE DROP FOREIGN KEY，需重建表。
     */
    public function up(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        // --- posts ---
        $this->recreateWithoutForeignKeys('posts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('threads_account_id')->index();
            $table->string('threads_media_id')->nullable();
            $table->string('text', 500)->nullable();
            $table->string('image_path')->nullable();
            $table->timestamp('scheduled_at');
            $table->timestamp('published_at')->nullable();
            $table->string('status')->default('draft');
            $table->text('error_message')->nullable();
            $table->unsignedInteger('publish_attempts')->default(0);
            $table->timestamps();
        });

        // --- replies ---
        $this->recreateWithoutForeignKeys('replies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('threads_account_id')->index();
            $table->unsignedBigInteger('post_id')->nullable()->index();
            $table->string('threads_reply_id')->nullable();
            $table->string('author_username');
            $table->text('text');
            $table->string('source')->default('polling');
            $table->string('status')->default('new');
            $table->text('error_message')->nullable();
            $table->unsignedInteger('publish_attempts')->default(0);
            $table->timestamp('replied_at')->nullable();
            $table->timestamps();
        });

        // --- threads_accounts ---
        $this->recreateWithoutForeignKeys('threads_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('threads_user_id')->unique();
            $table->string('username');
            $table->string('name')->nullable();
            $table->string('avatar')->nullable();
            $table->text('access_token');
            $table->timestamp('token_expires_at');
            $table->string('status')->default('active');
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });

        DB::statement('PRAGMA foreign_keys = ON');
    }

    /**
     * 還原：重建含 FK 的表（保留原始 FK 定義）。
     */
    public function down(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        // --- posts ---
        $this->recreateWithoutForeignKeys('posts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('threads_account_id');
            $table->string('threads_media_id')->nullable();
            $table->string('text', 500)->nullable();
            $table->string('image_path')->nullable();
            $table->timestamp('scheduled_at');
            $table->timestamp('published_at')->nullable();
            $table->string('status')->default('draft');
            $table->text('error_message')->nullable();
            $table->unsignedInteger('publish_attempts')->default(0);
            $table->timestamps();

            $table->foreign('threads_account_id')->references('id')->on('threads_accounts')->cascadeOnDelete();
        });

        // --- replies ---
        $this->recreateWithoutForeignKeys('replies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('threads_account_id');
            $table->unsignedBigInteger('post_id')->nullable();
            $table->string('threads_reply_id')->nullable();
            $table->string('author_username');
            $table->text('text');
            $table->string('source')->default('polling');
            $table->string('status')->default('new');
            $table->text('error_message')->nullable();
            $table->unsignedInteger('publish_attempts')->default(0);
            $table->timestamp('replied_at')->nullable();
            $table->timestamps();

            $table->foreign('threads_account_id')->references('id')->on('threads_accounts')->cascadeOnDelete();
            $table->foreign('post_id')->references('id')->on('posts')->cascadeOnDelete();
        });

        // --- threads_accounts ---
        $this->recreateWithoutForeignKeys('threads_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('threads_user_id')->unique();
            $table->string('username');
            $table->string('name')->nullable();
            $table->string('avatar')->nullable();
            $table->text('access_token');
            $table->timestamp('token_expires_at');
            $table->string('status')->default('active');
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        DB::statement('PRAGMA foreign_keys = ON');
    }

    /**
     * SQLite 重建表（無 FK）。
     */
    private function recreateWithoutForeignKeys(string $table, callable $blueprint): void
    {
        $tempTable = $table.'_temp';

        // 清理可能殘留的 temp 表
        Schema::dropIfExists($tempTable);

        // 建立新表
        Schema::create($tempTable, $blueprint);

        // 複製資料
        $columns = Schema::getColumnListing($table);
        $columnsStr = implode(', ', $columns);
        DB::statement("INSERT INTO {$tempTable} ({$columnsStr}) SELECT {$columnsStr} FROM {$table}");

        // 替換
        Schema::drop($table);
        Schema::rename($tempTable, $table);
    }
};
