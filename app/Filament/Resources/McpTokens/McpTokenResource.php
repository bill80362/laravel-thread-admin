<?php

namespace App\Filament\Resources\McpTokens;

use App\Filament\Resources\McpTokens\Pages\ListMcpTokens;
use App\Filament\Resources\McpTokens\Tables\McpTokensTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Passport\Token;

class McpTokenResource extends Resource
{
    protected static ?string $model = Token::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static ?string $navigationLabel = 'MCP 控管';

    protected static ?string $modelLabel = 'MCP Token';

    protected static ?int $navigationSort = 50;

    public static function table(Table $table): Table
    {
        return McpTokensTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMcpTokens::route('/'),
        ];
    }
}
