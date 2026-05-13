<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use PostHog\PostHog;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (! config('posthog.disabled')) {
            PostHog::init(
                config('posthog.api_key'),
                ['host' => config('posthog.host')]
            );
        }
    }
}
