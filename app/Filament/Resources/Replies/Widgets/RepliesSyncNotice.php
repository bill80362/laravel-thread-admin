<?php

namespace App\Filament\Resources\Replies\Widgets;

use App\Jobs\CollectThreadsReplies;
use App\Jobs\PublishScheduledPost;
use Filament\Widgets\Widget;

class RepliesSyncNotice extends Widget
{
    /**
     * @var view-string
     */
    protected string $view = 'filament.widgets.replies-sync-notice';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'syncInterval' => CollectThreadsReplies::SYNC_INTERVAL_MINUTES,
            'publishDelaySeconds' => PublishScheduledPost::PUBLISH_DELAY_SECONDS,
        ];
    }
}
