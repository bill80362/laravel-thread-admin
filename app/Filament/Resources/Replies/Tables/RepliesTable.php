<?php

namespace App\Filament\Resources\Replies\Tables;

use App\Enums\ReplyStatus;
use App\Models\Reply;
use App\Services\ThreadsClient;
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
                    ->formatStateUsing(fn (string $state): string => "@{$state}")
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
                    ->badge()
                    ->color(fn (ReplyStatus $state): string => match ($state) {
                        ReplyStatus::New => 'danger',
                        ReplyStatus::Replied => 'success',
                        ReplyStatus::Ignored => 'gray',
                    })
                    ->formatStateUsing(fn (ReplyStatus $state): string => match ($state) {
                        ReplyStatus::New => '未回覆',
                        ReplyStatus::Replied => '已回覆',
                        ReplyStatus::Ignored => '已忽略',
                    }),

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
                    ->label('回覆')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->visible(fn (Reply $record): bool => $record->status === ReplyStatus::New)
                    ->form([
                        Textarea::make('text')
                            ->label('回覆內容')
                            ->required()
                            ->maxLength(500)
                            ->rows(3),
                    ])
                    ->action(function (Reply $record, array $data, ThreadsClient $threads): void {
                        $account = $record->threadsAccount;

                        try {
                            $creationId = $threads->createTextContainer($account, $data['text'], $record->threads_reply_id);
                            $threads->publishContainer($account, $creationId);

                            $record->update([
                                'status' => ReplyStatus::Replied,
                                'replied_at' => now(),
                            ]);

                            Notification::make()
                                ->title('已回覆')
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('回覆失敗')
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
