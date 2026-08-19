<?php

namespace App\Filament\Resources\ThreadsAccounts\Pages;

use App\Filament\Resources\ThreadsAccounts\ThreadsAccountResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListThreadsAccounts extends ListRecords
{
    protected static string $resource = ThreadsAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('bindAccount')
                ->label('綁定 Threads 帳號')
                ->icon('heroicon-o-link')
                ->url(route('threads.oauth.redirect')),
        ];
    }
}
