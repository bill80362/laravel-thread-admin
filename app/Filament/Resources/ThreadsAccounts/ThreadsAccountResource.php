<?php

namespace App\Filament\Resources\ThreadsAccounts;

use App\Filament\Resources\ThreadsAccounts\Pages\ListThreadsAccounts;
use App\Filament\Resources\ThreadsAccounts\Schemas\ThreadsAccountForm;
use App\Filament\Resources\ThreadsAccounts\Tables\ThreadsAccountsTable;
use App\Models\ThreadsAccount;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ThreadsAccountResource extends Resource
{
    protected static ?string $model = ThreadsAccount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $navigationLabel = 'Threads 帳號';

    protected static ?string $modelLabel = 'Threads 帳號';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return ThreadsAccountForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ThreadsAccountsTable::configure($table);
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
            'index' => ListThreadsAccounts::route('/'),
        ];
    }
}
