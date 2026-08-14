<?php

namespace App\Filament\Resources\Posts\Schemas;

use App\Enums\ThreadsAccountStatus;
use App\Models\ThreadsAccount;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('threads_account_id')
                    ->label('目標帳號')
                    ->relationship('threadsAccount', 'username')
                    ->getOptionLabelFromRecordUsing(fn ($record) => "@{$record->username}")
                    ->required()
                    ->helperText(fn ($get) => self::getAccountWarning($get('threads_account_id'))),

                Textarea::make('text')
                    ->label('貼文內容')
                    ->required()
                    ->maxLength(500)
                    ->rows(4)
                    ->helperText('最多 500 字元'),

                DateTimePicker::make('scheduled_at')
                    ->label('排程時間')
                    ->required()
                    ->minDate(now())
                    ->native(false),
            ]);
    }

    private static function getAccountWarning(mixed $accountId): ?string
    {
        if ($accountId === null) {
            return null;
        }

        $account = ThreadsAccount::query()->find($accountId);

        if ($account?->status === ThreadsAccountStatus::NeedsReauth) {
            return '⚠️ 此帳號需要重新授權，發佈時可能失敗';
        }

        return null;
    }
}
