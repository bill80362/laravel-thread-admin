<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('基本資訊')
                    ->schema([
                        TextInput::make('name')
                            ->label('名稱')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        TextInput::make('new_password')
                            ->label(fn (string $operation): string => $operation === 'create' ? '密碼' : '新密碼（留空不修改）')
                            ->password()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->nullable(fn (string $operation): bool => $operation !== 'create')
                            ->minLength(8)
                            ->dehydrateStateUsing(fn (?string $state): ?string => $state ? Hash::make($state) : null)
                            ->dehydrated(fn (?string $state): bool => filled($state)),
                    ])
                    ->columns(2),

                Section::make('控管設定')
                    ->schema([
                        TextInput::make('max_accounts')
                            ->label('最大綁定帳號數')
                            ->integer()
                            ->required()
                            ->default(3)
                            ->minValue(0),

                        TextInput::make('max_daily_posts')
                            ->label('每日發文上限')
                            ->integer()
                            ->required()
                            ->default(10)
                            ->minValue(0),

                        TextInput::make('max_daily_replies')
                            ->label('每日回覆上限')
                            ->integer()
                            ->required()
                            ->default(50)
                            ->minValue(0),

                        Toggle::make('is_active')
                            ->label('啟用')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }
}
