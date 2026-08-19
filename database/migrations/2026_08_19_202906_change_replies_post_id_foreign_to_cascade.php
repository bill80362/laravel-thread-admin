<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // SQLite 不支援修改外鍵，需重建資料表
        DB::statement('PRAGMA foreign_keys = OFF');

        DB::statement('CREATE TABLE replies_new (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            threads_account_id INTEGER NOT NULL,
            post_id INTEGER,
            threads_reply_id VARCHAR,
            author_username VARCHAR NOT NULL,
            text VARCHAR NOT NULL,
            source VARCHAR DEFAULT \'polling\' NOT NULL,
            status VARCHAR DEFAULT \'new\' NOT NULL,
            error_message TEXT,
            publish_attempts INTEGER DEFAULT 0 NOT NULL,
            replied_at DATETIME,
            created_at DATETIME,
            updated_at DATETIME,
            FOREIGN KEY (threads_account_id) REFERENCES threads_accounts(id) ON DELETE CASCADE,
            FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
        )');

        DB::statement('INSERT INTO replies_new SELECT id, user_id, threads_account_id, post_id, threads_reply_id, author_username, text, source, status, error_message, publish_attempts, replied_at, created_at, updated_at FROM replies');
        DB::statement('DROP TABLE replies');
        DB::statement('ALTER TABLE replies_new RENAME TO replies');
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS replies_threads_reply_id_unique ON replies(threads_reply_id)');

        DB::statement('PRAGMA foreign_keys = ON');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        DB::statement('CREATE TABLE replies_old (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            threads_account_id INTEGER NOT NULL,
            post_id INTEGER,
            threads_reply_id VARCHAR,
            author_username VARCHAR NOT NULL,
            text VARCHAR NOT NULL,
            source VARCHAR DEFAULT \'polling\' NOT NULL,
            status VARCHAR DEFAULT \'new\' NOT NULL,
            error_message TEXT,
            publish_attempts INTEGER DEFAULT 0 NOT NULL,
            replied_at DATETIME,
            created_at DATETIME,
            updated_at DATETIME,
            FOREIGN KEY (threads_account_id) REFERENCES threads_accounts(id) ON DELETE CASCADE,
            FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE SET NULL
        )');

        DB::statement('INSERT INTO replies_old SELECT id, user_id, threads_account_id, post_id, threads_reply_id, author_username, text, source, status, error_message, publish_attempts, replied_at, created_at, updated_at FROM replies');
        DB::statement('DROP TABLE replies');
        DB::statement('ALTER TABLE replies_old RENAME TO replies');
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS replies_threads_reply_id_unique ON replies(threads_reply_id)');

        DB::statement('PRAGMA foreign_keys = ON');
    }
};
