<?php

use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\HomeSectionController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ResourceDownloadController;
use App\Http\Controllers\SearchEngineController;
use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

Route::permanentRedirect('/', '/en');
Route::get('/robots.txt', [SearchEngineController::class, 'robots'])->name('robots');
Route::get('/sitemap.xml', [SearchEngineController::class, 'sitemap'])->name('sitemap');

Route::middleware('guest')->group(function (): void {
    Route::get('/admin/login', [AuthController::class, 'create'])->name('login');
    Route::post('/admin/login', [AuthController::class, 'store'])->middleware('throttle:10,1')->name('login.store');
});

Route::prefix('admin')->name('admin.')->middleware('admin')->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

    Route::get('/homepage', [HomeSectionController::class, 'edit'])->name('home-sections.index');
    Route::put('/homepage', [HomeSectionController::class, 'update'])->name('home-sections.update');
    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.index');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::get('/resources/{resource}/download', [ResourceDownloadController::class, 'admin'])->whereNumber('resource')->name('resources.download');

    Route::get('/content/{type}', [ContentController::class, 'index'])->name('content.index');
    Route::get('/content/{type}/create', [ContentController::class, 'create'])->name('content.create');
    Route::post('/content/{type}', [ContentController::class, 'store'])->name('content.store');
    Route::get('/content/{type}/{item}/edit', [ContentController::class, 'edit'])->whereNumber('item')->name('content.edit');
    Route::put('/content/{type}/{item}', [ContentController::class, 'update'])->whereNumber('item')->name('content.update');
    Route::delete('/content/{type}/{item}', [ContentController::class, 'destroy'])->whereNumber('item')->name('content.destroy');
});

Route::prefix('{locale}')->where(['locale' => 'en|fr|ar|pt|es|sw'])->middleware('locale')->group(function (): void {
    Route::get('/', [SiteController::class, 'home'])->name('home');
    Route::get('/about', [SiteController::class, 'about'])->name('about');
    Route::get('/events', [SiteController::class, 'events'])->name('events.index');
    Route::get('/events/{slug}', [SiteController::class, 'event'])->name('events.show');
    Route::get('/news', [SiteController::class, 'news'])->name('news.index');
    Route::get('/news/{slug}', [SiteController::class, 'newsPost'])->name('news.show');
    Route::get('/program-outline', [SiteController::class, 'programs'])->name('programs');
    Route::get('/resources', [SiteController::class, 'resources'])->name('resources.index');
    Route::get('/resources/{resource}/download', ResourceDownloadController::class)->whereNumber('resource')->name('resources.download');
    Route::get('/faq', [SiteController::class, 'faq'])->name('faq');
});
