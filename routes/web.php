<?php

use App\Http\Controllers\ThreadsOAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('threads/oauth')->group(function () {
    Route::get('redirect', [ThreadsOAuthController::class, 'redirect'])->name('threads.oauth.redirect');
    Route::get('callback', [ThreadsOAuthController::class, 'callback'])->name('threads.oauth.callback');
});
