<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Database\Factories\EventResourceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'event_id', 'title', 'description', 'category', 'language', 'file_path',
    'original_filename', 'mime_type', 'file_size', 'is_published', 'sort_order',
])]
class EventResource extends Model
{
    /** @use HasFactory<EventResourceFactory> */
    use HasFactory;

    use HasTranslations;

    public const CATEGORIES = [
        'programme' => 'Programme outline',
        'brief' => 'Event brief',
        'presentation' => 'Presentation',
        'report' => 'Report',
    ];

    protected $hidden = ['file_path'];

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
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'title' => 'array',
            'description' => 'array',
            'file_size' => 'integer',
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
