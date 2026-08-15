<?php

namespace App\Filament\Resources\Replies\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ReplyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('threads_account_id')
                    ->label('來源帳號')
                    ->relationship('threadsAccount', 'username')
                    ->getOptionLabelFromRecordUsing(fn ($record) => "@{$record->username}")
                    ->required(),

                Select::make('post_id')
                    ->label('所屬貼文')
                    ->relationship('post', 'text')
                    ->getOptionLabelFromRecordUsing(fn ($record) => mb_strimwidth($record->text, 0, 40, '...'))
                    ->nullable(),

                TextInput::make('author_username')
                    ->label('留言者')
                    ->prefix('@')
                    ->required()
                    ->maxLength(255),

                Textarea::make('text')
                    ->label('留言內容')
                    ->required()
                    ->maxLength(500)
                    ->rows(4),
            ]);
    }
}
