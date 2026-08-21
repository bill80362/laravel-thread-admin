<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * 將 replies.user_id 為 null 的記錄，依 threads_account_id 對應的帳號 user_id 回填。
     */
    public function up(): void
    {
        DB::table('replies')
            ->whereNull('user_id')
            ->whereIn('threads_account_id', DB::table('threads_accounts')->select('id'))
            ->update([
                'user_id' => DB::raw('(SELECT user_id FROM threads_accounts WHERE threads_accounts.id = replies.threads_account_id)'),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 資料已改寫，無法安全還原；保留為空操作。
    }
};
