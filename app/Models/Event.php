<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'slug',
    'title',
    'excerpt',
    'body',
    'venue',
    'start_at',
    'end_at',
    'mode',
    'registration_url',
    'image',
    'is_featured',
    'is_published',
])]
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    use HasTranslations;

    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class);
    }

    public function resources(): HasMany
    {
        return $this->hasMany(EventResource::class);
    }

    public function registrationUrlForLocale(string $locale): ?string
    {
        $url = trim((string) $this->registration_url);
        $locales = array_keys(config('locales.supported'));

        if (! in_array($locale, $locales, true) || ! self::isSafeRegistrationUrl($url)) {
            return null;
        }

        if (! str_starts_with($url, '/')) {
            return $url;
        }

        $path = (string) parse_url($url, PHP_URL_PATH);
        $suffix = substr($url, strlen($path));
        $localePattern = '#^/(?:'.implode('|', array_map(fn (string $code): string => preg_quote($code, '#'), $locales)).')(?=/|$)#';
        $path = preg_replace($localePattern, '', $path);

        return '/'.$locale.($path === '/' ? '' : $path).$suffix;
    }

    public function registrationUrlOpensExternally(): bool
    {
        $url = trim((string) $this->registration_url);

        return self::isSafeRegistrationUrl($url) && ! str_starts_with($url, '/');
    }

    public static function isSafeRegistrationUrl(string $url): bool
    {
        $url = trim($url);
        $decodedUrl = rawurldecode($url);

        if ($url === '' || preg_match('/[\x00-\x20\x7F\\\\]/', $url) || preg_match('/[\x00-\x1F\x7F\\\\]/', $decodedUrl)) {
            return false;
        }

        if (str_starts_with($url, '/')) {
            return ! str_starts_with($decodedUrl, '//');
        }

        return filter_var($url, FILTER_VALIDATE_URL) !== false
            && in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true);
    }

    #[Scope]
    protected function currentOrUpcoming(Builder $query): void
    {
        $query->where(function (Builder $current): void {
            $current->where('end_at', '>=', now())->orWhere(function (Builder $upcoming): void {
                $upcoming->whereNull('end_at')->where('start_at', '>=', now()->startOfDay());
            });
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'title' => 'array',
            'excerpt' => 'array',
            'body' => 'array',
            'venue' => 'array',
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'is_featured' => 'boolean',
            'is_published' => 'boolean',
        ];
    }
}
