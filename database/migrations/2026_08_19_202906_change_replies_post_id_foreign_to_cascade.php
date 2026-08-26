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
        // 專案規則：不使用資料庫外鍵約束，關聯僅在 Model 層透過 Eloquent 實現。
        // 此 migration 原本僅調整 post_id 外鍵行為，因最終會移除所有外鍵，
        // 故這裡僅確保欄位型別一致，不建立任何外鍵約束。
        Schema::table('replies', function (Blueprint $table) {
            $table->unsignedBigInteger('post_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('replies', function (Blueprint $table) {
            $table->unsignedBigInteger('post_id')->nullable()->change();
        });
    }
};
