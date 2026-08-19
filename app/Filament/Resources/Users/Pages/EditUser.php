<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['new_password']) && filled($data['new_password'])) {
            $data['password'] = $data['new_password'];
        }
        unset($data['new_password']);

        return $data;
    }
}
