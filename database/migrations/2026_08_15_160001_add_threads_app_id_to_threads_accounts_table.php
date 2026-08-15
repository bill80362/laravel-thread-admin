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
        Schema::table('threads_accounts', function (Blueprint $table) {
            $table->foreignId('threads_app_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('threads_accounts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('threads_app_id');
        });
    }
};
