<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'eyebrow', 'title', 'body', 'image', 'is_published'])]
class Page extends Model
{
    use HasTranslations;

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
            'body' => 'array',
            'is_published' => 'boolean',
        ];
    }
}
