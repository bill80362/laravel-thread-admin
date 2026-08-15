<?php

namespace App\Filament\Resources\ThreadsAccounts\Tables;

use App\Enums\ThreadsAccountStatus;
use App\Models\ThreadsAccount;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ThreadsAccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('threadsApp.name')
                    ->label('所屬 App')
                    ->searchable()
                    ->sortable(),

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
                SelectFilter::make('threads_app_id')
                    ->label('App')
                    ->relationship('threadsApp', 'name'),
            ])
            ->recordActions([
                self::reauthorizeAction(),
                DeleteAction::make()
                    ->label('解除綁定')
                    ->modalHeading('解除綁定 Threads 帳號')
                    ->modalDescription('解除綁定後，該帳號的未發排程文章將一併取消。'),
            ])
            ->toolbarActions([]);
    }

    private static function reauthorizeAction(): Action
    {
        return Action::make('reauthorize')
            ->label('重新授權')
            ->icon('heroicon-o-arrow-path')
            ->visible(fn (ThreadsAccount $record): bool => $record->threads_app_id !== null)
            ->url(fn (ThreadsAccount $record) => route('threads.oauth.redirect', [
                'app' => $record->threads_app_id,
                'account' => $record->id,
            ]));
    }
}
