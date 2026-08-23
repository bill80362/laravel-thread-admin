<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class UsageGuide extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static ?string $navigationLabel = '使用說明';

    protected static ?int $navigationSort = 60;

    protected static ?string $title = '使用說明';

    protected string $view = 'filament.pages.usage-guide';

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('一、排程發文')
                ->description('了解貼文狀態流程與自動發佈機制')
                ->schema([
                    View::make('filament.pages.usage-guide.chapter1'),
                ]),

            Section::make('二、回覆收集')
                ->description('了解回覆收集範圍與頻率')
                ->schema([
                    View::make('filament.pages.usage-guide.chapter2'),
                ]),

            Section::make('三、MCP 服務設定（AI 工具串接）')
                ->description('設定 ChatGPT 或 Claude Desktop 串接本系統')
                ->schema([
                    View::make('filament.pages.usage-guide.chapter3'),
                ]),
        ]);
    }
}
