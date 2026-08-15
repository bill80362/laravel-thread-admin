<?php

namespace App\Filament\Resources\ThreadsApps;

use App\Filament\Resources\ThreadsApps\Pages\CreateThreadsApp;
use App\Filament\Resources\ThreadsApps\Pages\EditThreadsApp;
use App\Filament\Resources\ThreadsApps\Pages\ListThreadsApps;
use App\Filament\Resources\ThreadsApps\Schemas\ThreadsAppForm;
use App\Filament\Resources\ThreadsApps\Tables\ThreadsAppsTable;
use App\Models\ThreadsApp;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ThreadsAppResource extends Resource
{
    protected static ?string $model = ThreadsApp::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Threads App';

    protected static ?string $modelLabel = 'Threads App';

    public static function form(Schema $schema): Schema
    {
        return ThreadsAppForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ThreadsAppsTable::configure($table);
    }

    /**
     * 每個登入人員只能看到並管理自己建立的 App。
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

    public static function getPages(): array
    {
        return [
            'index' => ListThreadsApps::route('/'),
            'create' => CreateThreadsApp::route('/create'),
            'edit' => EditThreadsApp::route('/{record}/edit'),
        ];
    }
}
