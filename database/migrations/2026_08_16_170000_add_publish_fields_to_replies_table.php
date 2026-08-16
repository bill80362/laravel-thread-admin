<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('replies', function (Blueprint $table) {
            $table->text('error_message')->nullable()->after('status');
            $table->unsignedInteger('publish_attempts')->default(0)->after('error_message');
        });

        DB::table('replies')
            ->where('source', 'manual')
            ->whereNull('threads_reply_id')
            ->delete();
    }

    public function down(): void
    {
        Schema::table('replies', function (Blueprint $table) {
            $table->dropColumn(['error_message', 'publish_attempts']);
        });
    }
};
