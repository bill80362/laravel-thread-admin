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
        // 1. 移除 threads_accounts 的 threads_app_id 外鍵與欄位
        Schema::table('threads_accounts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('threads_app_id');
        });

        // 2. 移除 threads_oauth_states 的 threads_app_id 外鍵與欄位
        Schema::table('threads_oauth_states', function (Blueprint $table) {
            $table->dropConstrainedForeignId('threads_app_id');
        });

        // 3. 刪除 threads_apps 表格
        Schema::dropIfExists('threads_apps');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 重建 threads_apps 表格
        Schema::create('threads_apps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('client_id');
            $table->text('client_secret');
            $table->timestamps();
        });

        // 恢復 threads_accounts.threads_app_id
        Schema::table('threads_accounts', function (Blueprint $table) {
            $table->foreignId('threads_app_id')->nullable()->after('id')->constrained('threads_apps')->nullOnDelete();
        });

        // 恢復 threads_oauth_states.threads_app_id
        Schema::table('threads_oauth_states', function (Blueprint $table) {
            $table->foreignId('threads_app_id')->nullable()->after('id')->constrained('threads_apps')->cascadeOnDelete();
        });
    }
};
