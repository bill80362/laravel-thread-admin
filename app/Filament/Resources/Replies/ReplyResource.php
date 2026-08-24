<?php

namespace App\Filament\Resources\Replies;

use App\Filament\Resources\Replies\Pages\CreateReply;
use App\Filament\Resources\Replies\Pages\ListReplies;
use App\Filament\Resources\Replies\Schemas\ReplyForm;
use App\Filament\Resources\Replies\Tables\RepliesTable;
use App\Models\Reply;
use App\Services\ReplyService;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReplyResource extends Resource
{
    protected static ?string $model = Reply::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $navigationLabel = '回覆面板';

    protected static ?string $modelLabel = '回覆';

    protected static ?string $pluralModelLabel = '回覆';

    protected static ?int $navigationSort = 40;

    public static function form(Schema $schema): Schema
    {
        return ReplyForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RepliesTable::configure($table);
    }

    /**
     * 每位登入人員僅能看到自己的資料。
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $service = app(ReplyService::class);

        return $service->unreadTotalCount().'/'.$service->totalCount();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $unread = app(ReplyService::class)->unreadTotalCount();

        return $unread > 0 ? 'warning' : 'gray';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReplies::route('/'),
            'create' => CreateReply::route('/create'),
        ];
    }
}
