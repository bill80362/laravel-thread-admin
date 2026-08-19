<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Enums\ThreadsAccountStatus;
use Filament\Actions\DeleteAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ThreadsAccountsRelationManager extends RelationManager
{
    protected static string $relationship = 'threadsAccounts';

    protected static ?string $title = 'Threads 帳號';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('username')
                    ->label('帳號')
                    ->searchable(),

                TextColumn::make('name')
                    ->label('名稱'),

                TextColumn::make('status')
                    ->label('狀態')
                    ->badge()
                    ->color(fn (ThreadsAccountStatus $state): string => match ($state) {
                        ThreadsAccountStatus::Active => 'success',
                        ThreadsAccountStatus::NeedsReauth => 'danger',
                        ThreadsAccountStatus::Disabled => 'gray',
                    })
                    ->formatStateUsing(fn (ThreadsAccountStatus $state): string => match ($state) {
                        ThreadsAccountStatus::Active => '已綁定',
                        ThreadsAccountStatus::NeedsReauth => '需重新授權',
                        ThreadsAccountStatus::Disabled => '已停用',
                    }),

                TextColumn::make('token_expires_at')
                    ->label('Token 到期日')
                    ->dateTime('Y-m-d H:i'),
            ])
            ->actions([
                DeleteAction::make()
                    ->label('取消綁定且刪除')
                    ->modalHeading('取消綁定且刪除 Threads 帳號')
                    ->modalDescription('確定要取消綁定且刪除嗎？該帳號下的所有貼文與回覆記錄將一併刪除，此操作無法復原。注意：不會刪除 Threads 上的實際貼文。'),
            ]);
    }
}
