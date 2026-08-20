<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Panel Branding
    |--------------------------------------------------------------------------
    |
    | 各 Filament 面板的對外品牌名稱。這些名稱僅用於介面顯示（logo、分頁
    | 標題、登入頁），與內部識別名 APP_NAME 解耦，因此可支援中文等非 ASCII
    | 字元，不會影響 cache / session 等內部 prefix 設定。
    |
    */

    'admin' => [
        'name' => env('ADMIN_BRAND', 'SocialMediaAdmin'),
    ],

    'user' => [
        'name' => env('USER_BRAND', 'SocialMediaAdmin'),
    ],

];
