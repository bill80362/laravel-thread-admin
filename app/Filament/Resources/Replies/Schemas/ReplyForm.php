<?php

namespace App\Filament\Resources\Replies\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ReplyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('threads_account_id')
                    ->label('來源帳號')
                    ->relationship('threadsAccount', 'username', fn ($query) => $query->where('user_id', auth()->id()))
                    ->getOptionLabelFromRecordUsing(fn ($record) => "@{$record->username}")
                    ->required(),

                Select::make('post_id')
                    ->label('目標貼文')
                    ->relationship('post', 'text', fn ($query) => $query->where('user_id', auth()->id())->whereNotNull('threads_media_id'))
                    ->getOptionLabelFromRecordUsing(fn ($record) => mb_strimwidth($record->text, 0, 40, '...'))
                    ->required(),

                Textarea::make('text')
                    ->label('回覆內容')
                    ->required()
                    ->maxLength(500)
                    ->rows(4),
            ]);
    }
}
