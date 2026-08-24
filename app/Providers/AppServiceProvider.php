<?php

namespace App\Providers;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        View::composer('*', function ($view) {
            $siteSetting = SiteSetting::current();

            $view->with([
                'siteSetting' => $siteSetting,
                'siteName' => $siteSetting->site_name ?: config('app.name'),
                'favicon' => $siteSetting->favicon_url,
                'siteLogo' => $siteSetting->logo_url,
            ]);
        });
    }
}
