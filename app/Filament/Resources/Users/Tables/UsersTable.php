<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\Post;
use App\Models\Reply;
use App\Models\ThreadsAccount;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('名稱')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('account_usage')
                    ->label('帳號數')
                    ->state(fn (User $record): string => sprintf(
                        '%d/%d',
                        ThreadsAccount::where('user_id', $record->id)->count(),
                        $record->max_accounts,
                    )),

                TextColumn::make('daily_post_usage')
                    ->label('今日發文')
                    ->state(fn (User $record): string => sprintf(
                        '%d/%d',
                        Post::where('user_id', $record->id)->whereDate('created_at', today())->count(),
                        $record->max_daily_posts,
                    )),

                TextColumn::make('daily_reply_usage')
                    ->label('今日回覆')
                    ->state(fn (User $record): string => sprintf(
                        '%d/%d',
                        Reply::where('user_id', $record->id)->whereDate('created_at', today())->count(),
                        $record->max_daily_replies,
                    )),

                IconColumn::make('is_active')
                    ->label('啟用')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('建立時間')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                DeleteAction::make()
                    ->label('刪除')
                    ->modalHeading('刪除使用者')
                    ->modalDescription('確定要刪除此使用者嗎？該使用者下的所有帳號、貼文、回覆與 MCP Token 將一併刪除，此操作無法復原。注意：不會刪除 Threads 上的實際貼文。'),
            ]);
    }
}
