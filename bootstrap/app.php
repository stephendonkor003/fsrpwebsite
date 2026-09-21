<?php

use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\SearchVisibility;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SearchVisibility::class);
        $middleware->alias([
            'admin' => EnsureAdmin::class,
            'locale' => SetLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash([
            'title',
            'first_name',
            'surname',
            'gender',
            'date_of_birth',
            'nationality',
            'national_id_number',
            'passport_number',
            'passport_expiry_date',
            'issuing_country',
            'visa_required',
            'organisation',
            'member_state',
            'delegation_capacity',
            'years_in_service',
            'areas_of_expertise',
            'mobile_number',
            'alternative_phone',
            'official_email',
            'personal_email',
            'emergency_contact',
            'arrival_date',
            'departure_date',
            'dietary_requirements',
            'other_dietary_needs',
            'dinner_attendance',
            'data_protection_declaration',
            'attendance_confirmation',
            'website',
        ]);

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
