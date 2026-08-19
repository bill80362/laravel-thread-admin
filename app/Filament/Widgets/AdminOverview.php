<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('使用者總數', User::query()->count())
                ->description('所有使用者')
                ->color('primary'),

            Stat::make('啟用中', User::query()->where('is_active', true)->count())
                ->description('已啟用的使用者')
                ->color('success'),

            Stat::make('已停用', User::query()->where('is_active', false)->count())
                ->description('已停用的使用者')
                ->color('danger'),
        ];
    }
}
