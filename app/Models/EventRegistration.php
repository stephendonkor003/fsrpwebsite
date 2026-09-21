<?php

namespace App\Models;

use App\Casts\EncryptedDate;
use Database\Factories\EventRegistrationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable([
    'public_id',
    'event_id',
    'event_slug',
    'event_title',
    'event_venue',
    'event_start_at',
    'event_end_at',
    'locale',
    'title',
    'first_name',
    'surname',
    'gender',
    'date_of_birth',
    'nationality',
    'national_id_number',
    'passport_number',
    'passport_expiry_date',
    'issuing_country',
    'visa_required',
    'passport_photo_path',
    'passport_photo_original_name',
    'passport_scan_path',
    'passport_scan_original_name',
    'organisation',
    'member_state',
    'delegation_capacity',
    'years_in_service',
    'areas_of_expertise',
    'mobile_number',
    'alternative_phone',
    'official_email',
    'official_email_hash',
    'verified_email_hash',
    'personal_email',
    'emergency_contact',
    'arrival_date',
    'departure_date',
    'dietary_requirements',
    'other_dietary_needs',
    'dinner_attendance',
    'consent_version',
    'consent_text_hash',
    'data_protection_accepted_at',
    'attendance_confirmed_at',
    'official_email_verified_at',
    'confirmation_email_status',
    'confirmation_email_queued_at',
    'confirmation_email_sent_at',
    'confirmation_email_failed_at',
    'receipt_email_status',
    'receipt_email_queued_at',
    'receipt_email_sent_at',
    'receipt_email_failed_at',
])]
class EventRegistration extends Model
{
    public const EMAIL_PENDING = 'pending';

    public const EMAIL_QUEUED = 'queued';

    public const EMAIL_SENDING = 'sending';

    public const EMAIL_SENT = 'sent';

    public const EMAIL_FAILED = 'failed';

    /** @use HasFactory<EventRegistrationFactory> */
    use HasFactory, Prunable;

    protected $hidden = [
        'national_id_number',
        'passport_number',
        'mobile_number',
        'alternative_phone',
        'personal_email',
        'official_email_hash',
        'verified_email_hash',
        'emergency_contact',
        'passport_photo_path',
        'passport_scan_path',
    ];

    protected static function booted(): void
    {
        static::creating(function (EventRegistration $registration): void {
            $registration->public_id ??= (string) Str::uuid();
        });

        static::saving(function (EventRegistration $registration): void {
            if ($registration->isDirty('official_email')) {
                $registration->official_email = Str::lower(trim((string) $registration->official_email));
                $registration->official_email_hash = self::emailHash($registration->official_email);
            }
        });

        static::deleted(function (EventRegistration $registration): void {
            Storage::disk('local')->deleteDirectory('event-registrations/'.$registration->public_id);
        });
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function fullName(): string
    {
        return trim(implode(' ', array_filter([$this->title, $this->first_name, $this->surname])));
    }

    public static function emailHash(string $email): string
    {
        return hash_hmac('sha256', Str::lower(trim($email)), (string) config('app.key'));
    }

    public function hasVerifiedOfficialEmail(): bool
    {
        return $this->official_email_verified_at !== null
            && is_string($this->official_email_hash)
            && $this->official_email_hash !== ''
            && is_string($this->verified_email_hash)
            && hash_equals($this->official_email_hash, $this->verified_email_hash);
    }

    /** @return Builder<EventRegistration> */
    public function prunable(): Builder
    {
        $unverifiedCutoff = now()->subDays(
            max(1, (int) config('seed_summit.unverified_retention_days', 30)),
        );
        $eventCutoff = now()->subDays(
            max(1, (int) config('seed_summit.retention_days_after_event', 365)),
        );

        return static::query()
            ->where('event_slug', config('seed_summit.event_slug'))
            ->where(function (Builder $query) use ($unverifiedCutoff, $eventCutoff): void {
                $query
                    ->where(function (Builder $unverified) use ($unverifiedCutoff): void {
                        $unverified
                            ->whereNull('official_email_verified_at')
                            ->where('created_at', '<=', $unverifiedCutoff);
                    })
                    ->orWhere(function (Builder $pastEvent) use ($eventCutoff): void {
                        $pastEvent
                            ->whereNotNull('event_end_at')
                            ->where('event_end_at', '<=', $eventCutoff);
                    });
            });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'title' => 'encrypted',
            'first_name' => 'encrypted',
            'surname' => 'encrypted',
            'gender' => 'encrypted',
            'date_of_birth' => EncryptedDate::class,
            'nationality' => 'encrypted',
            'passport_expiry_date' => EncryptedDate::class,
            'issuing_country' => 'encrypted',
            'visa_required' => 'boolean',
            'years_in_service' => 'integer',
            'personal_email' => 'encrypted',
            'official_email' => 'encrypted',
            'national_id_number' => 'encrypted',
            'passport_number' => 'encrypted',
            'mobile_number' => 'encrypted',
            'alternative_phone' => 'encrypted',
            'emergency_contact' => 'encrypted',
            'passport_photo_original_name' => 'encrypted',
            'passport_scan_original_name' => 'encrypted',
            'organisation' => 'encrypted',
            'member_state' => 'encrypted',
            'delegation_capacity' => 'encrypted',
            'areas_of_expertise' => 'encrypted',
            'arrival_date' => EncryptedDate::class,
            'departure_date' => EncryptedDate::class,
            'dietary_requirements' => 'encrypted',
            'other_dietary_needs' => 'encrypted',
            'dinner_attendance' => 'boolean',
            'event_start_at' => 'datetime',
            'event_end_at' => 'datetime',
            'data_protection_accepted_at' => 'datetime',
            'attendance_confirmed_at' => 'datetime',
            'official_email_verified_at' => 'datetime',
            'confirmation_email_queued_at' => 'datetime',
            'confirmation_email_sent_at' => 'datetime',
            'confirmation_email_failed_at' => 'datetime',
            'receipt_email_queued_at' => 'datetime',
            'receipt_email_sent_at' => 'datetime',
            'receipt_email_failed_at' => 'datetime',
        ];
    }
}
