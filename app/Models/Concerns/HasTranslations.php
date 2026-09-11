<?php

namespace App\Models\Concerns;

trait HasTranslations
{
    public function translate(string $field, ?string $locale = null): ?string
    {
        $translations = $this->getAttribute($field);

        if (! is_array($translations)) {
            return is_string($translations) && $translations !== '' ? $translations : null;
        }

        $locale = $locale ?? app()->getLocale();

        if (isset($translations[$locale]) && is_string($translations[$locale]) && $translations[$locale] !== '') {
            return $translations[$locale];
        }

        if (isset($translations['en']) && is_string($translations['en']) && $translations['en'] !== '') {
            return $translations['en'];
        }

        foreach ($translations as $translation) {
            if (is_string($translation) && $translation !== '') {
                return $translation;
            }
        }

        return null;
    }
}
