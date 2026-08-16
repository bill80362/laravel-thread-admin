<?php

namespace App\Filament\Resources\McpTokens\Pages;

use App\Filament\Resources\McpTokens\McpTokenResource;
use Filament\Resources\Pages\ListRecords;

class ListMcpTokens extends ListRecords
{
    protected static string $resource = McpTokenResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
