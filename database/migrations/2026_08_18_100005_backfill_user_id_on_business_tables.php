<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('threads_accounts')->whereNull('user_id')->update(['user_id' => 2]);
        DB::table('posts')->whereNull('user_id')->update(['user_id' => 2]);
        DB::table('replies')->whereNull('user_id')->update(['user_id' => 2]);
        DB::table('threads_oauth_states')->whereNull('user_id')->update(['user_id' => 2]);
    }

    public function down(): void
    {
        // 資料已改寫，無法安全還原；保留為空操作。
    }
};
