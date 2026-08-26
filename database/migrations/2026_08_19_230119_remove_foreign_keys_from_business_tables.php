<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 移除 posts、replies、threads_accounts 的所有 FK。
     * 專案規則：不使用資料庫外鍵約束，關聯僅在 Model 層透過 Eloquent 實現。
     * 使用 Schema builder 的 dropForeign()，會依資料庫引擎自動產生對應 SQL
     * （SQLite 內部重建資料表、MariaDB 使用 ALTER TABLE DROP FOREIGN KEY）。
     */
    public function up(): void
    {
        // 僅移除實際存在的外鍵（由 create_* migration 的 constrained() 建立）：
        // - posts: threads_account_id
        // - replies: threads_account_id、post_id
        // user_id 欄位僅有 index、無外鍵，不需 dropForeign。
        Schema::table('posts', function (Blueprint $table) {
            $table->dropForeign(['threads_account_id']);
        });

        Schema::table('replies', function (Blueprint $table) {
            $table->dropForeign(['threads_account_id']);
            $table->dropForeign(['post_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 專案規則不使用外鍵，還原時不重新建立 FK。
    }
};
