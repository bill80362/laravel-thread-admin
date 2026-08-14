<?php

namespace App\Filament\Resources\ThreadsAccounts\Pages;

use App\Filament\Resources\ThreadsAccounts\ThreadsAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListThreadsAccounts extends ListRecords
{
    protected static string $resource = ThreadsAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
