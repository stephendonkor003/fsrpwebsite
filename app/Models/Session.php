<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'event_id',
    'title',
    'summary',
    'speaker_name',
    'speaker_role',
    'location',
    'track',
    'start_at',
    'end_at',
    'format',
    'sort_order',
    'is_published',
    'is_all_day',
])]
class Session extends Model
{
    use HasTranslations;

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('is_published', true)->where(function (Builder $nested): void {
            $nested->whereNull('event_id')->orWhereHas('event', fn (Builder $event) => $event->where('is_published', true));
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
            'summary' => 'array',
            'speaker_name' => 'array',
            'speaker_role' => 'array',
            'location' => 'array',
            'track' => 'array',
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'sort_order' => 'integer',
            'is_published' => 'boolean',
            'is_all_day' => 'boolean',
        ];
    }
}
