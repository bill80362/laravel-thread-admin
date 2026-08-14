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
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('threads_account_id')->constrained()->cascadeOnDelete();
            $table->string('threads_media_id')->nullable();
            $table->string('text', 500);
            $table->timestamp('scheduled_at');
            $table->timestamp('published_at')->nullable();
            $table->string('status')->default('draft');
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
