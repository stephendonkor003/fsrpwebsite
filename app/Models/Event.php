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
