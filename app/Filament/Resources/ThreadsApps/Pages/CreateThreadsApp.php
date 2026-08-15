<?php

namespace App\Filament\Resources\ThreadsApps\Pages;

use App\Filament\Resources\ThreadsApps\ThreadsAppResource;
use Filament\Resources\Pages\CreateRecord;

class CreateThreadsApp extends CreateRecord
{
    protected static string $resource = ThreadsAppResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        return $data;
    }
}
