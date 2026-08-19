<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (isset($data['new_password']) && filled($data['new_password'])) {
            $data['password'] = $data['new_password'];
        }
        unset($data['new_password']);

        return $data;
    }
}
