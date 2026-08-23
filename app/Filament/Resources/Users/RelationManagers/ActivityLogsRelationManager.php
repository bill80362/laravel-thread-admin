<?php

namespace App\Filament\Resources\Users\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ActivityLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'activityLogs';

    protected static ?string $title = '發送紀錄';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('發送時間')
                    ->dateTime('Y-m-d H:i:s'),

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

                TextColumn::make('reference_id')
                    ->label('關聯 ID')
                    ->placeholder('-'),
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
