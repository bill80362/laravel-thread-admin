<?php

namespace App\Filament\Resources\Replies\Pages;

use App\Filament\Resources\Replies\ReplyResource;
use App\Services\ReplyService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateReply extends CreateRecord
{
    protected static string $resource = ReplyResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(ReplyService::class)->createPostReply(
            (int) $data['threads_account_id'],
            (int) $data['post_id'],
            $data['text'],
        );
    }
}
