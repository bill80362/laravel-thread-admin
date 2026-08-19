<?php

namespace App\Filament\Resources\Posts\Pages;

use App\Enums\PostStatus;
use App\Filament\Resources\Posts\PostResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditPost extends EditRecord
{
    protected static string $resource = PostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var \App\Models\Post $record */
        $record = $this->getRecord();

        // 只有 Draft 和 Scheduled 狀態才能編輯
        if (! in_array($record->status, [PostStatus::Draft, PostStatus::Scheduled])) {
            throw ValidationException::withMessages([
                'status' => '只有草稿或排程中的貼文才能編輯',
            ]);
        }

        if (! empty($data['scheduled_at'])) {
            $data['status'] = PostStatus::Scheduled->value;
        }

        return $data;
    }
}
