<?php

namespace App\Filament\Resources\Posts\Pages;

use App\Enums\PostStatus;
use App\Filament\Resources\Posts\PostResource;
use App\Models\Post;
use App\Services\PostService;
use App\Services\ReplyService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
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

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
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
                            ->weight('bold'),

                        TextColumn::make('status')
                            ->badge(),

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
                            ->size('sm'),
                    ])->space(1)
                        ->extraAttributes(['class' => 'p-3 min-h-[120px]']),
                ])->space(0)
                    ->extraAttributes(['class' => 'bg-white rounded-lg shadow-sm overflow-hidden border border-gray-200']),
            ])
            ->recordActions([
                Action::make('viewReplies')
                    ->label('回覆')
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
