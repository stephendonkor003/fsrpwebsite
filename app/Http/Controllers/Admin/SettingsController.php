<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings.index', [
            'settings' => Setting::all()->keyBy('key'),
            'locales' => config('locales.supported'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $locales = array_keys(config('locales.supported'));
        $translatedKeys = ['site_name', 'tagline', 'address', 'footer_blurb', 'copyright'];
        $plainKeys = ['contact_email', 'contact_phone', 'facebook_url', 'linkedin_url', 'youtube_url'];
        $rules = [
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];

        foreach ($translatedKeys as $key) {
            foreach ($locales as $locale) {
                $rules["settings.$key.$locale"] = [$locale === 'en' && in_array($key, ['site_name', 'tagline'], true) ? 'required' : 'nullable', 'string', 'max:2000'];
            }
        }

        foreach ($plainKeys as $key) {
            $rules["settings.$key"] = match ($key) {
                'contact_email' => ['nullable', 'email', 'max:255'],
                'facebook_url', 'linkedin_url', 'youtube_url' => ['nullable', 'url', 'max:2048'],
                default => ['nullable', 'string', 'max:255'],
            };
        }

        $validated = $request->validate($rules);

        foreach ($translatedKeys as $key) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $validated['settings'][$key] ?? [], 'group' => in_array($key, ['footer_blurb', 'copyright'], true) ? 'footer' : 'general'],
            );
        }

        foreach ($plainKeys as $key) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => ['value' => $validated['settings'][$key] ?? ''], 'group' => Str::contains($key, '_url') ? 'social' : 'contact'],
            );
        }

        if ($request->hasFile('logo')) {
            $existing = Setting::where('key', 'logo')->first();
            $oldPath = data_get($existing?->value, 'value');
            if (is_string($oldPath) && Str::startsWith($oldPath, '/storage/')) {
                Storage::disk('public')->delete(Str::after($oldPath, '/storage/'));
            }

            Setting::updateOrCreate(
                ['key' => 'logo'],
                ['value' => ['value' => '/storage/'.$request->file('logo')->store('branding', 'public')], 'group' => 'general'],
            );
        }

        return back()->with('status', 'Site settings updated successfully.');
    }
}
