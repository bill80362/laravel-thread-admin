<?php

namespace App\Filament\Resources\Posts\Schemas;

use App\Enums\ThreadsAccountStatus;
use App\Models\ThreadsAccount;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Operation;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('貼文狀態資訊')
                    ->schema([
                        TextEntry::make('status')
                            ->label('狀態')
                            ->badge(),
                        TextEntry::make('published_at')
                            ->label('發佈時間')
                            ->dateTime('Y-m-d H:i')
                            ->placeholder('-'),
                        TextEntry::make('error_message')
                            ->label('錯誤訊息')
                            ->placeholder('-'),
                    ])
                    ->columns(3)
                    ->hiddenOn(Operation::Create),

                Select::make('threads_account_id')
                    ->label('目標帳號')
                    ->relationship('threadsAccount', 'username', fn ($query) => $query->where('user_id', auth()->id()))
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
                    ->default(now())
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
