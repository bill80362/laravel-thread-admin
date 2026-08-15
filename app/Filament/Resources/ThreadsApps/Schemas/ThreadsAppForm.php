<?php

namespace App\Filament\Resources\ThreadsApps\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ThreadsAppForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('名稱')
                    ->required(),
                TextInput::make('client_id')
                    ->label('Client ID')
                    ->required(),
                TextInput::make('client_secret')
                    ->label('Client Secret')
                    ->password()
                    ->revealable()
                    ->required()
                    ->columnSpanFull(),
            ]);
    }
}
