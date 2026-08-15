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
        Schema::create('threads_oauth_states', function (Blueprint $table) {
            $table->id();
            $table->string('token_hash')->unique();
            $table->foreignId('threads_app_id')->constrained()->cascadeOnDelete();
            $table->foreignId('threads_account_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('threads_oauth_states');
    }
};
