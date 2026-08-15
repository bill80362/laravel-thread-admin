<?php

namespace App\Filament\Resources\Posts\Tables;

use App\Enums\PostStatus;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('threadsAccount.username')
                    ->label('帳號')
                    ->formatStateUsing(fn (?string $state): string => $state ? "@{$state}" : '-')
                    ->sortable(),

                TextColumn::make('text')
                    ->label('內容')
                    ->limit(50)
                    ->searchable(),

                TextColumn::make('status')
                    ->label('狀態')
                    ->badge(),

                TextColumn::make('scheduled_at')
                    ->label('排程時間')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),

                TextColumn::make('published_at')
                    ->label('發佈時間')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('error_message')
                    ->label('錯誤訊息')
                    ->limit(30)
                    ->placeholder('-'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn ($record) => in_array($record->status, [PostStatus::Draft, PostStatus::Scheduled])),
                DeleteAction::make()
                    ->visible(fn ($record) => in_array($record->status, [PostStatus::Draft, PostStatus::Scheduled])),
            ]);
    }
}
