<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'eyebrow',
    'title',
    'subtitle',
    'button_text',
    'button_url',
    'image',
    'video_url',
    'is_active',
    'sort_order',
])]
class Slide extends Model
{
    use HasTranslations;

    public function buttonUrlForLocale(string $locale): ?string
    {
        $url = trim((string) $this->button_url);
        $locales = array_keys(config('locales.supported'));

        if (! in_array($locale, $locales, true) || ! self::isSafeButtonUrl($url)) {
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

    public static function isSafeButtonUrl(string $url): bool
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

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'eyebrow' => 'array',
            'title' => 'array',
            'subtitle' => 'array',
            'button_text' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
