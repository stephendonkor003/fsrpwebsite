<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['question', 'answer', 'category', 'sort_order', 'is_published'])]
class Faq extends Model
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
            'question' => 'array',
            'answer' => 'array',
            'category' => 'array',
            'sort_order' => 'integer',
            'is_published' => 'boolean',
        ];
    }
}
