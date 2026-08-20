<?php

namespace App\Filament\Resources\Posts\Schemas;

use App\Enums\PostStatus;
use App\Enums\ThreadsAccountStatus;
use App\Models\ThreadsAccount;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Operation;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // 最上方整列：貼文狀態資訊（僅編輯時顯示）
                Section::make('貼文狀態資訊')
                    ->schema([
                        TextEntry::make('status')
                            ->label('狀態')
                            ->badge(),
                        TextEntry::make('published_at')
                            ->label('發佈時間')
                            ->dateTime('Y-m-d H:i')
                            ->placeholder('-'),
                        TextEntry::make('error_message')
                            ->label('錯誤訊息')
                            ->placeholder('-'),
                    ])
                    ->columns(3)
                    ->columnSpanFull()
                    ->hiddenOn(Operation::Create),

                Hidden::make('status'),

                Grid::make(['lg' => 3])
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        // 左欄：圖片上傳區（桌面佔 1 欄，約 40%），手機單欄時優先顯示
                        Repeater::make('images')
                            ->label('圖片')
                            ->relationship()
                            ->schema([
                                FileUpload::make('image_path')
                                    ->label('圖片檔案')
                                    ->image()
                                    ->disk('public')
                                    ->directory('posts')
                                    ->acceptedFileTypes(['image/jpeg', 'image/png'])
                                    ->maxSize(8192),
                            ])
                            ->orderColumn('sort_order')
                            ->reorderable()
                            ->maxItems(10)
                            ->addActionLabel('新增圖片')
                            ->columns(1)
                            ->columnSpan(['lg' => 1])
                            ->columnOrder(['default' => 1])
                            ->disabled(fn ($get, string $operation): bool => $operation === 'edit' && ! in_array($get('status'), [PostStatus::Draft->value, PostStatus::Scheduled->value]))
                            ->helperText('支援 JPEG、PNG，最大 8MB，最多 10 張。文字與圖片至少需填寫一項。'),

                        // 右欄：表單欄位（桌面佔 2 欄，較寬），手機端時在圖片之後
                        Grid::make(1)
                            ->columnSpan(['lg' => 2])
                            ->columnOrder(['default' => 2])
                            ->schema([
                                Select::make('threads_account_id')
                                    ->label('目標帳號')
                                    ->relationship('threadsAccount', 'username', fn ($query) => $query->where('user_id', auth()->id()))
                                    ->getOptionLabelFromRecordUsing(fn ($record) => "@{$record->username}")
                                    ->default(fn () => ThreadsAccount::query()->where('user_id', auth()->id())->orderBy('id')->value('id'))
                                    ->required()
                                    ->columnSpanFull()
                                    ->disabled(fn ($get, string $operation): bool => $operation === 'edit' && ! in_array($get('status'), [PostStatus::Draft->value, PostStatus::Scheduled->value]))
                                    ->helperText(fn ($get) => self::getAccountWarning($get('threads_account_id'))),

                                DateTimePicker::make('scheduled_at')
                                    ->label('排程時間')
                                    ->required()
                                    ->default(now())
                                    ->native(false)
                                    ->columnSpanFull()
                                    ->disabled(fn ($get, string $operation): bool => $operation === 'edit' && ! in_array($get('status'), [PostStatus::Draft->value, PostStatus::Scheduled->value])),

                                Textarea::make('text')
                                    ->label('貼文內容')
                                    ->nullable()
                                    ->maxLength(500)
                                    ->rows(4)
                                    ->columnSpanFull()
                                    ->disabled(fn ($get, string $operation): bool => $operation === 'edit' && ! in_array($get('status'), [PostStatus::Draft->value, PostStatus::Scheduled->value]))
                                    ->helperText('最多 500 字元。文字與圖片至少需填寫一項。'),
                            ]),
                    ]),
            ]);
    }

    private static function getAccountWarning(mixed $accountId): ?string
    {
        if ($accountId === null) {
            return null;
        }

        $account = ThreadsAccount::query()->find($accountId);

        if ($account?->status === ThreadsAccountStatus::NeedsReauth) {
            return '⚠️ 此帳號需要重新授權，發佈時可能失敗';
        }

        return null;
    }
}
