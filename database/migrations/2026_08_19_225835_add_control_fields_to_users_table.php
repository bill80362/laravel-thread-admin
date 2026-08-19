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
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('max_accounts')->default(3)->after('password');
            $table->unsignedInteger('max_daily_posts')->default(10)->after('max_accounts');
            $table->unsignedInteger('max_daily_replies')->default(50)->after('max_daily_posts');
            $table->boolean('is_active')->default(true)->after('max_daily_replies');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['max_accounts', 'max_daily_posts', 'max_daily_replies', 'is_active']);
        });
    }
};
