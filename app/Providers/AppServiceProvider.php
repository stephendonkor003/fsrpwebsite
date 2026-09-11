<?php

namespace App\Providers;

use App\AfricaMap;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewInstance;

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
        View::composer('site.partials.africa-map', function (ViewInstance $view): void {
            $view->with('africaMap', app(AfricaMap::class)->data(app()->getLocale()));
        });
    }
}
