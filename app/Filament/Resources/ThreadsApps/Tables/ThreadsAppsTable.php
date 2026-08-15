<?php

namespace App\Filament\Resources\ThreadsApps\Tables;

use App\Models\ThreadsApp;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ThreadsAppsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('名稱')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('client_id')
                    ->label('Client ID')
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label('建立時間')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                self::bindAccountAction(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                self::bindAccountAction(),
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    private static function bindAccountAction(): Action
    {
        return Action::make('bindAccount')
            ->label('綁定 Threads 帳號')
            ->icon('heroicon-o-link')
            ->url(fn (ThreadsApp $record) => route('threads.oauth.redirect', ['app' => $record]));
    }
}
