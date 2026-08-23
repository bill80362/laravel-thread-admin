<?php

namespace App\Filament\Resources\ActivityLogs\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ActivityLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('發送時間')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),

                TextColumn::make('type')
                    ->label('類型')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'post' ? 'primary' : 'success')
                    ->formatStateUsing(fn (string $state): string => $state === 'post' ? '貼文' : '回覆'),

                TextColumn::make('threadsAccount.username')
                    ->label('帳號')
                    ->formatStateUsing(fn (?string $state): string => $state ? "@{$state}" : '-'),

                TextColumn::make('text')
                    ->label('內容')
                    ->limit(50)
                    ->placeholder('（內容已刪除）'),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('類型')
                    ->options([
                        'post' => '貼文',
                        'reply' => '回覆',
                    ]),
            ])
            ->actions([]);
    }
}
