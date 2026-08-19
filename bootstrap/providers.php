<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\UserPanelProvider;

return [
    AppServiceProvider::class,
    UserPanelProvider::class,
    AdminPanelProvider::class,
];
