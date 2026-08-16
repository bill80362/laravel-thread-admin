<?php

namespace App\Filament\Resources\McpTokens\Tables;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Laravel\Passport\Token;

class McpTokensTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('Token ID')
                    ->formatStateUsing(fn (string $state): string => substr($state, 0, 12).'…')
                    ->tooltip(fn (Token $record): string => $record->id),

                TextColumn::make('client.name')
                    ->label('來源')
                    ->searchable(),

                TextColumn::make('client_id')
                    ->label('Client ID')
                    ->formatStateUsing(fn (string $state): string => substr($state, 0, 12).'…'),

                TextColumn::make('scopes')
                    ->label('授權範圍')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => is_array($state) ? implode(', ', $state) : '-'),

                TextColumn::make('created_at')
                    ->label('建立時間')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),

                TextColumn::make('expires_at')
                    ->label('到期時間')
                    ->dateTime('Y-m-d H:i')
                    ->placeholder('永久有效'),

                TextColumn::make('revoked')
                    ->label('狀態')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'danger' : 'success')
                    ->formatStateUsing(fn (bool $state): string => $state ? '已註銷' : '有效'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('revoke')
                    ->label('註銷')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (Token $record): bool => ! $record->revoked)
                    ->requiresConfirmation()
                    ->modalHeading('確認註銷')
                    ->modalDescription('註銷後，該 token 將無法再存取 MCP 服務。確定要註銷嗎？')
                    ->action(function (Token $record): void {
                        $record->revoke();

                        Notification::make()
                            ->title('已註銷')
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
