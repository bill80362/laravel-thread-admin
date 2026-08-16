<?php

namespace App\Filament\Resources\Replies\Tables;

use App\Enums\ReplyStatus;
use App\Models\Reply;
use App\Services\ReplyService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RepliesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('threadsAccount.username')
                    ->label('來源帳號')
                    ->formatStateUsing(fn (?string $state): string => $state ? "@{$state}" : '-'),

                TextColumn::make('author_username')
                    ->label('留言者')
                    ->formatStateUsing(fn (?string $state): string => $state ? "@{$state}" : '-')
                    ->searchable(),

                TextColumn::make('text')
                    ->label('留言內容')
                    ->wrap()
                    ->limit(100),

                TextColumn::make('post.text')
                    ->label('所屬貼文')
                    ->limit(40)
                    ->placeholder('-'),

                TextColumn::make('status')
                    ->label('狀態')
                    ->badge(),

                TextColumn::make('created_at')
                    ->label('時間')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('threads_account_id')
                    ->label('帳號')
                    ->relationship('threadsAccount', 'username'),
                SelectFilter::make('status')
                    ->label('狀態')
                    ->options(ReplyStatus::class),
            ])
            ->recordActions([
                Action::make('reply')
                    ->label('回應回覆')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->visible(fn (Reply $record): bool => $record->status === ReplyStatus::New)
                    ->form([
                        Textarea::make('text')
                            ->label('回應內容')
                            ->required()
                            ->maxLength(500)
                            ->rows(3),
                    ])
                    ->action(function (Reply $record, array $data, ReplyService $replies): void {
                        try {
                            $replies->publish($record, $data['text']);

                            Notification::make()
                                ->title('已排程回應')
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('回應失敗')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('ignore')
                    ->label('忽略')
                    ->icon('heroicon-o-no-symbol')
                    ->color('gray')
                    ->visible(fn (Reply $record): bool => $record->status === ReplyStatus::New)
                    ->requiresConfirmation()
                    ->action(function (Reply $record): void {
                        $record->update(['status' => ReplyStatus::Ignored]);

                        Notification::make()
                            ->title('已忽略')
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
