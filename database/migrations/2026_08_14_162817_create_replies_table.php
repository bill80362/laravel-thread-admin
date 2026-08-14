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
        Schema::create('replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('threads_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('post_id')->nullable()->constrained()->nullOnDelete();
            $table->string('threads_reply_id')->unique();
            $table->string('author_username');
            $table->string('text');
            $table->string('source')->default('polling');
            $table->string('status')->default('new');
            $table->timestamp('replied_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('replies');
    }
};
