<?php

namespace App\Filament\Resources\ThreadsApps\Pages;

use App\Filament\Resources\ThreadsApps\ThreadsAppResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListThreadsApps extends ListRecords
{
    protected static string $resource = ThreadsAppResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
