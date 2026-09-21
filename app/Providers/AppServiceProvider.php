<?php

namespace App\Providers;

use App\AfricaMap;
use App\Mail\Transport\MicrosoftGraphTransport;
use App\Models\EventRegistration;
use App\Services\Mail\MicrosoftGraphMailService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\View\View as ViewInstance;
use Symfony\Component\HttpFoundation\Response;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(MicrosoftGraphMailService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('seed-summit-registration', function (Request $request): array {
            $email = Str::lower(trim((string) $request->input('official_email', 'guest')));
            $emailAndIp = hash('sha256', $email.'|'.$request->ip());
            $ip = hash('sha256', (string) $request->ip());

            return [
                Limit::perMinute(20)->by('v2:attempt:'.$emailAndIp),
                Limit::perHour(3)
                    ->by('v2:email:'.EventRegistration::emailHash($email))
                    ->after(fn (Response $response): bool => $response->isRedirection()
                        && str_contains((string) $response->headers->get('Location'), '/registrations/')),
                Limit::perMinute(120)->by('v2:ip:'.$ip),
            ];
        });

        Mail::extend(
            'graph',
            fn (array $config = []): MicrosoftGraphTransport => new MicrosoftGraphTransport(
                $this->app->make(MicrosoftGraphMailService::class),
            ),
        );

        View::composer('site.partials.africa-map', function (ViewInstance $view): void {
            $view->with('africaMap', app(AfricaMap::class)->data(app()->getLocale()));
        });
    }
}
