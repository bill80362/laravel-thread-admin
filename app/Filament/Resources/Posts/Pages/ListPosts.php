<?php

namespace App\Filament\Resources\Posts\Pages;

use App\Enums\PostStatus;
use App\Filament\Resources\Posts\PostResource;
use App\Models\ActivityLog;
use App\Models\Post;
use App\Services\PostService;
use App\Services\ReplyService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class ListPosts extends ListRecords
{
    protected static string $resource = PostResource::class;

    protected string $view = 'filament.resources.posts.pages.list-posts';

    /**
     * 抽屜是否開啟。
     */
    public bool $replyDrawerOpen = false;

    /**
     * 抽屜目前顯示的貼文 ID。
     */
    public ?int $replyDrawerPostId = null;

    /**
     * 開啟某貼文的回覆抽屜。
     */
    public function openReplyDrawer(int $postId): void
    {
        $this->replyDrawerPostId = $postId;
        $this->replyDrawerOpen = true;
    }

    /**
     * 關閉回覆抽屜。
     */
    public function closeReplyDrawer(): void
    {
        $this->replyDrawerOpen = false;
        $this->replyDrawerPostId = null;
    }

    /**
     * 取得今日用量資料供 Blade view 使用。
     *
     * @return array{post_sent: int, post_max: int, post_scheduled: int, reply_sent: int, reply_max: int}
     */
    public function getDailyUsageData(): array
    {
        $userId = auth()->id();
        $user = auth()->user();

        $postSent = ActivityLog::countTodayForUser($userId, 'post');
        $postScheduled = Post::query()
            ->where('user_id', $userId)
            ->where('status', PostStatus::Scheduled)
            ->whereDate('scheduled_at', today())
            ->count();
        $replySent = ActivityLog::countTodayForUser($userId, 'reply');

        return [
            'post_sent' => $postSent,
            'post_max' => $user?->max_daily_posts ?? 0,
            'post_scheduled' => $postScheduled,
            'reply_sent' => $replySent,
            'reply_max' => $user?->max_daily_replies ?? 0,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(function () {
                $usage = $this->getDailyUsageData();
                $parts = [];
                if ($usage['post_max'] > 0) {
                    $parts[] = "📊 今日發文 {$usage['post_sent']}/{$usage['post_max']}";
                }
                if ($usage['reply_max'] > 0) {
                    $parts[] = "回覆 {$usage['reply_sent']}/{$usage['reply_max']}";
                }

                return implode(' · ', $parts);
            })
            ->description(function () {
                $usage = $this->getDailyUsageData();
                $parts = [];
                if ($usage['post_max'] > 0) {
                    $remaining = max(0, $usage['post_max'] - $usage['post_sent']);
                    $parts[] = "已發送 {$usage['post_sent']} 篇，剩餘 {$remaining} 篇";
                    if ($usage['post_scheduled'] > 0) {
                        $parts[] = "排程中今日將發送 {$usage['post_scheduled']} 篇";
                    }
                }
                if ($usage['reply_max'] > 0) {
                    $remaining = max(0, $usage['reply_max'] - $usage['reply_sent']);
                    $parts[] = "已回覆 {$usage['reply_sent']} 則，剩餘 {$remaining} 則";
                }

                return implode(' · ', $parts);
            })
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('狀態')
                    ->options(PostStatus::class),

                SelectFilter::make('threads_account_id')
                    ->label('帳號')
                    ->relationship('threadsAccount', 'username', modifyQueryUsing: fn (Builder $query): Builder => $query->where('user_id', auth()->id())),

                Filter::make('published_at_range')
                    ->label('發佈時間')
                    ->schema([
                        DatePicker::make('published_from')
                            ->label('從'),
                        DatePicker::make('published_until')
                            ->label('到'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['published_from'] ?? null,
                                fn (Builder $query, string $date): Builder => $query->whereDate('published_at', '>=', $date),
                            )
                            ->when(
                                $data['published_until'] ?? null,
                                fn (Builder $query, string $date): Builder => $query->whereDate('published_at', '<=', $date),
                            );
                    }),

                Filter::make('scheduled_at_range')
                    ->label('排程時間')
                    ->schema([
                        DatePicker::make('scheduled_from')
                            ->label('從'),
                        DatePicker::make('scheduled_until')
                            ->label('到'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['scheduled_from'] ?? null,
                                fn (Builder $query, string $date): Builder => $query->whereDate('scheduled_at', '>=', $date),
                            )
                            ->when(
                                $data['scheduled_until'] ?? null,
                                fn (Builder $query, string $date): Builder => $query->whereDate('scheduled_at', '<=', $date),
                            );
                    }),

                Filter::make('text_search')
                    ->label('內容')
                    ->schema([
                        TextInput::make('text')
                            ->label('內容關鍵字'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['text'] ?? null,
                            fn (Builder $query, string $keyword): Builder => $query->where('text', 'like', "%{$keyword}%"),
                        )),

                Filter::make('error_search')
                    ->label('錯誤訊息')
                    ->schema([
                        TextInput::make('error_message')
                            ->label('錯誤訊息關鍵字'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['error_message'] ?? null,
                            fn (Builder $query, string $keyword): Builder => $query->where('error_message', 'like', "%{$keyword}%"),
                        )),
            ])
            ->columns([
                Stack::make([
                    // 圖片區域：固定 aspect-ratio，永遠佔據相同高度
                    TextColumn::make('image_block')
                        ->label('')
                        ->html()
                        ->state(function (Post $record): string {
                            $image = $record->images->first();

                            if ($image) {
                                $url = Storage::disk('public')->url($image->image_path);

                                return sprintf(
                                    '<div class="h-48 w-full overflow-hidden rounded-t-lg bg-gray-100">
                                        <img src="%s" class="w-full h-full object-cover" loading="lazy" />
                                    </div>',
                                    e($url),
                                );
                            }

                            // 無圖片時用佔位圖
                            $placeholderUrl = Storage::disk('public')->url('global/no_image.png');

                            return sprintf(
                                '<div class="h-48 w-full overflow-hidden rounded-t-lg bg-gray-100">
                                    <img src="%s" class="w-full h-full object-contain p-8" loading="lazy" />
                                </div>',
                                e($placeholderUrl),
                            );
                        }),

                    // 內容區域：固定最小高度，確保一致
                    Stack::make([
                        TextColumn::make('threadsAccount.username')
                            ->formatStateUsing(fn (?string $state): string => $state ? "@{$state}" : '-')
                            ->weight('bold')
                            ->sortable(),

                        TextColumn::make('status')
                            ->badge()
                            ->sortable(),

                        TextColumn::make('unread_badge')
                            ->label('')
                            ->html()
                            ->state(fn (Post $record): int => app(ReplyService::class)->unreadCount($record->id))
                            ->formatStateUsing(fn (int $state): string => $state > 0 ? sprintf(
                                '<span class="inline-flex items-center gap-1 rounded-full bg-warning-100 px-2 py-0.5 text-xs font-medium text-warning-700">
                                    <span class="h-1.5 w-1.5 rounded-full bg-warning-500"></span>
                                    有新回覆
                                </span>',
                            ) : ''),

                        TextColumn::make('text')
                            ->limit(50)
                            ->extraAttributes(['class' => 'line-clamp-2 min-h-[2.5rem]']),

                        TextColumn::make('scheduled_at')
                            ->dateTime('m-d H:i')
                            ->color('gray')
                            ->size('sm')
                            ->sortable(),
                    ])->space(1)
                        ->extraAttributes(['class' => 'p-3 min-h-[120px]']),
                ])->space(0)
                    ->extraAttributes(['class' => 'bg-white rounded-lg shadow-sm overflow-hidden border border-gray-200']),
            ])
            ->defaultSort(function (Builder $query): Builder {
                return $query
                    ->orderByRaw('scheduled_at IS NULL')
                    ->orderBy('scheduled_at', 'desc');
            })
            ->recordActions([
                Action::make('viewReplies')
                    ->label(function (Post $record): string {
                        $unread = app(ReplyService::class)->unreadCount($record->id);
                        $total = app(ReplyService::class)->totalCountForPost($record->id);

                        if ($total === 0) {
                            return '回覆';
                        }

                        return "回覆 ({$unread}/{$total})";
                    })
                    ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                    ->action(fn (Post $record) => $this->openReplyDrawer($record->id)),

                EditAction::make()
                    ->visible(fn (Post $record) => in_array($record->status, [PostStatus::Draft, PostStatus::Scheduled])),
                DeleteAction::make()
                    ->visible(fn (Post $record) => ! in_array($record->status, [PostStatus::Deleting]))
                    ->action(function (Post $record) {
                        app(PostService::class)->delete($record->id);
                    }),
            ]);
    }
}
