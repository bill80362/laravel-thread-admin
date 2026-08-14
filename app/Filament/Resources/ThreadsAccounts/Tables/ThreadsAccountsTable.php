<?php

namespace App\Filament\Resources\ThreadsAccounts\Tables;

use App\Enums\ThreadsAccountStatus;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ThreadsAccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('username')
                    ->label('帳號')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name')
                    ->label('名稱')
                    ->searchable()
                    ->sortable(),

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
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),

                TextColumn::make('last_synced_at')
                    ->label('最後同步')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->placeholder('尚未同步'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                DeleteAction::make()
                    ->label('解除綁定')
                    ->modalHeading('解除綁定 Threads 帳號')
                    ->modalDescription('解除綁定後，該帳號的未發排程文章將一併取消。'),
            ])
            ->toolbarActions([
                Action::make('bindAccount')
                    ->label('綁定 Threads 帳號')
                    ->icon('heroicon-o-link')
                    ->url(route('threads.oauth.redirect')),
            ]);
    }
}
