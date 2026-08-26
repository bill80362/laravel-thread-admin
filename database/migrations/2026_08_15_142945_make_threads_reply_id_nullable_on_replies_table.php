<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 使用 Schema builder 的 change()，會依資料庫引擎自動產生對應 SQL
        // （SQLite 內部重建資料表、MariaDB 使用 ALTER TABLE MODIFY）
        Schema::table('replies', function (Blueprint $table) {
            $table->string('threads_reply_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('replies', function (Blueprint $table) {
            $table->string('threads_reply_id')->nullable(false)->change();
        });
    }
};
