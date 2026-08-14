<?php

namespace App\Filament\Widgets;

use App\Enums\ReplyStatus;
use App\Enums\ThreadsAccountStatus;
use App\Models\Reply;
use App\Models\ThreadsAccount;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ThreadsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('已綁定帳號', ThreadsAccount::query()->count())
                ->description('Threads 帳號總數')
                ->color('success'),

            Stat::make('待回覆留言', Reply::query()->where('status', ReplyStatus::New)->count())
                ->description('尚未處理的回覆')
                ->color('danger'),

            Stat::make('需重新授權', ThreadsAccount::query()->where('status', ThreadsAccountStatus::NeedsReauth)->count())
                ->description('token 失效的帳號')
                ->color('warning'),
        ];
    }
}
