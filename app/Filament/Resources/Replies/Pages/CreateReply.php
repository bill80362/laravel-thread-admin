<?php

namespace App\Filament\Resources\Replies\Pages;

use App\Enums\ReplySource;
use App\Enums\ReplyStatus;
use App\Filament\Resources\Replies\ReplyResource;
use Filament\Resources\Pages\CreateRecord;

class CreateReply extends CreateRecord
{
    protected static string $resource = ReplyResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['source'] = ReplySource::Manual->value;
        $data['status'] = ReplyStatus::New->value;

        return $data;
    }
}
