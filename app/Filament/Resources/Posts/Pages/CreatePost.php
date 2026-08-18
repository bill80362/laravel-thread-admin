<?php

namespace App\Filament\Resources\Posts\Pages;

use App\Enums\PostStatus;
use App\Filament\Resources\Posts\PostResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePost extends CreateRecord
{
    protected static string $resource = PostResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! empty($data['scheduled_at'])) {
            $data['status'] = PostStatus::Scheduled->value;
        }

        $data['user_id'] = auth()->id();

        return $data;
    }
}
