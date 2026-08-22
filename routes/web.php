<?php

use App\Http\Controllers\ThreadsOAuthController;
use App\Http\Controllers\ThreadsWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('threads/oauth')->group(function () {
    Route::get('redirect', [ThreadsOAuthController::class, 'redirect'])->name('threads.oauth.redirect');
    Route::get('callback', [ThreadsOAuthController::class, 'callback'])->name('threads.oauth.callback');
});

Route::prefix('threads')->group(function () {
    Route::match(['get', 'post'], 'webhook', [ThreadsWebhookController::class, 'handle'])->name('threads.webhook');
});
