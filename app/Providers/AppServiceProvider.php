<?php

namespace App\Providers;

use App\Services\ThreadsClient;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ThreadsClient::class, function ($app) {
            return new ThreadsClient(
                $app->make(ClientInterface::class),
            );
        });

        $this->app->bind(ClientInterface::class, function ($app) {
            return new Client([
                'timeout' => 30,
                'http_errors' => true,
            ]);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
