<?php

namespace App\Filament\Resources\Replies\Pages;

use App\Filament\Resources\Replies\ReplyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListReplies extends ListRecords
{
    protected static string $resource = ReplyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
