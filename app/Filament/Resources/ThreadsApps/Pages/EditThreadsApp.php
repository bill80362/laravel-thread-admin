<?php

namespace App\Filament\Resources\ThreadsApps\Pages;

use App\Filament\Resources\ThreadsApps\ThreadsAppResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditThreadsApp extends EditRecord
{
    protected static string $resource = ThreadsAppResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
