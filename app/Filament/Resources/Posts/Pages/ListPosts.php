<?php

namespace App\Filament\Resources\Posts\Pages;

use App\Enums\PostStatus;
use App\Filament\Resources\Posts\PostResource;
use App\Models\Post;
use App\Services\PostService;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ListPosts extends ListRecords
{
    protected static string $resource = PostResource::class;

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
                    ImageColumn::make('images.image_path')
                        ->label('')
                        ->disk('public')
                        ->height(200)
                        ->placeholder('無圖片'),

                    Stack::make([
                        TextColumn::make('threadsAccount.username')
                            ->formatStateUsing(fn (?string $state): string => $state ? "@{$state}" : '-'),

                        TextColumn::make('status')
                            ->badge(),

                        TextColumn::make('text')
                            ->limit(50),

                        TextColumn::make('scheduled_at')
                            ->dateTime('m-d H:i'),
                    ])->space(1),
                ])->space(2),
            ])
            ->recordActions([
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
