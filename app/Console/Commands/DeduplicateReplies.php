<?php

namespace App\Console\Commands;

use App\Enums\ReplySource;
use App\Enums\ReplyStatus;
use App\Models\Reply;
use Illuminate\Console\Command;

class DeduplicateReplies extends Command
{
    protected $signature = 'replies:deduplicate';

    protected $description = '刪除因 threads_reply_id 未回寫而重複匯入的回覆記錄';

    /**
     * 掃描所有 status=Replied 但 threads_reply_id 為 null 的回覆，
     * 尋找同一 post_id、相同 text、source=polling 的重複記錄並刪除。
     * 刪除前會先將重複記錄的 threads_reply_id 回填至原始手動回覆。
     */
    public function handle(): int
    {
        $repliedWithoutId = Reply::query()
            ->where('status', ReplyStatus::Replied)
            ->whereNull('threads_reply_id')
            ->get();

        if ($repliedWithoutId->isEmpty()) {
            $this->info('沒有需要清理的重複回覆。');

            return self::SUCCESS;
        }

        $totalDeleted = 0;
        $totalUpdated = 0;

        foreach ($repliedWithoutId as $original) {
            $duplicates = Reply::query()
                ->where('source', ReplySource::Polling)
                ->where('post_id', $original->post_id)
                ->where('text', $original->text)
                ->where('threads_account_id', $original->threads_account_id)
                ->where('id', '!=', $original->id)
                ->get();

            foreach ($duplicates as $dup) {
                // 將 polling 重複的 threads_reply_id 回填至原始手動回覆
                if ($dup->threads_reply_id !== null) {
                    $original->update(['threads_reply_id' => $dup->threads_reply_id]);
                    $totalUpdated++;
                }

                $dup->delete();
                $totalDeleted++;
            }
        }

        $this->info("清理完成！已回填 {$totalUpdated} 筆 threads_reply_id，已刪除 {$totalDeleted} 筆重複回覆。");

        return self::SUCCESS;
    }
}
