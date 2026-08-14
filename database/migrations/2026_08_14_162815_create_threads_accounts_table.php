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
        Schema::create('threads_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('threads_user_id')->unique();
            $table->string('username');
            $table->string('name')->nullable();
            $table->string('avatar')->nullable();
            $table->text('access_token');
            $table->timestamp('token_expires_at');
            $table->string('status')->default('active');
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('threads_accounts');
    }
};
