<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $clientId = (string) env('THREADS_CLIENT_ID');
        $clientSecret = (string) env('THREADS_CLIENT_SECRET');

        // 若舊的 .env 憑證不存在，則沒有可落地的資料。
        if ($clientId === '' || $clientSecret === '') {
            return;
        }

        // 取第一個存在的 User 作為 App 擁有者（無 User 則為 null）。
        $userId = DB::table('users')->value('id');

        $appId = DB::table('threads_apps')->insertGetId([
            'user_id' => $userId,
            'name' => '預設 Threads App',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 將既有帳號全部關聯到落地後的 App。
        DB::table('threads_accounts')
            ->whereNull('threads_app_id')
            ->update(['threads_app_id' => $appId]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 資料落地不可逆，僅在結構回滾時由其他 migration 移除欄位。
    }
};
