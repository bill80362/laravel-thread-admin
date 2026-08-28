<?php

namespace App\Providers;

use App\Services\ThreadsClient;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

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
                'curl' => [
                    CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                ],
            ]);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.force_https')) {
            URL::forceScheme('https');
        }

        Passport::authorizationView(fn ($parameters) => view('mcp.authorize', $parameters));
    }
}
