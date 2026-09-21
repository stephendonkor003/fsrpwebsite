<?php

namespace App\Http\Controllers;

use App\Actions\RegisterSeedSummitDelegate;
use App\Http\Requests\StoreEventRegistrationRequest;
use App\Jobs\SendSeedSummitRegistrationConfirmation;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Setting;
use App\Support\SeedSummitRegistrationPdf;
use App\Support\SeedSummitRegistrationSummary;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use JsonException;
use Throwable;

class SeedSummitRegistrationController extends Controller
{
    public function create(Request $request): Response
    {
        return response()->view('site.seed-summit.register', array_merge($this->sharedViewData(), [
            'event' => $this->event(),
            'formInput' => $this->formInput($request),
        ]))->withHeaders($this->privateHeaders());
    }

    public function store(
        StoreEventRegistrationRequest $request,
        RegisterSeedSummitDelegate $register,
    ): RedirectResponse {
        $registration = $register->execute(
            $this->event(),
            $request->registrationData(),
            app()->getLocale(),
            $request->file('passport_photo'),
            $request->file('passport_scan'),
        );

        $request->session()->put(
            $this->receiptSessionKey($registration),
            now()->addMinutes(max(1, (int) config('seed_summit.receipt_link_minutes', 30)))->timestamp,
        );

        try {
            $registration->update([
                'confirmation_email_status' => EventRegistration::EMAIL_QUEUED,
                'confirmation_email_queued_at' => now(),
            ]);

            Bus::dispatch(
                new SendSeedSummitRegistrationConfirmation($registration),
            );
        } catch (Throwable $exception) {
            $registration->update([
                'confirmation_email_status' => EventRegistration::EMAIL_FAILED,
                'confirmation_email_failed_at' => now(),
            ]);
            Log::error('Seed Summit confirmation email could not be queued.', [
                'exception' => $exception::class,
                'registration_reference' => $registration->public_id,
            ]);
        }

        return redirect()->to($this->signedUrl('seed-summit.registrations.show', $registration));
    }

    public function show(
        string $locale,
        EventRegistration $registration,
        Request $request,
        SeedSummitRegistrationSummary $summary,
    ): Response {
        $this->ensureSeedSummitRegistration($registration);
        $this->ensureReceiptSession($request, $registration);

        return response()->view('site.seed-summit.confirmation', array_merge($this->sharedViewData(), [
            'event' => $registration->event,
            'registration' => $registration,
            'summarySections' => $summary->for($registration),
            'pdfUrl' => $this->signedUrl('seed-summit.registrations.pdf', $registration),
            'emailStatus' => $registration->confirmation_email_status,
        ]))->withHeaders($this->privateHeaders());
    }

    public function pdf(
        string $locale,
        EventRegistration $registration,
        Request $request,
        SeedSummitRegistrationPdf $pdf,
    ): Response {
        $this->ensureSeedSummitRegistration($registration);
        $this->ensureReceiptSession($request, $registration);

        return response($pdf->render($registration), 200, array_merge($this->privateHeaders(), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="seed-summit-registration-'.$registration->public_id.'.pdf"',
        ]));
    }

    public function showEmailVerification(string $locale, EventRegistration $registration): Response
    {
        $this->ensureSeedSummitRegistration($registration);

        return response()->view('site.seed-summit.email-verified', array_merge($this->sharedViewData(), [
            'event' => $registration->event,
            'alreadyVerified' => $registration->official_email_verified_at !== null,
            'verificationCompleted' => false,
        ]))->withHeaders($this->privateHeaders());
    }

    public function verifyEmail(string $locale, EventRegistration $registration): Response
    {
        $this->ensureSeedSummitRegistration($registration);
        $alreadyVerified = false;

        if ($registration->official_email_verified_at === null) {
            try {
                $registration->update([
                    'official_email_verified_at' => now(),
                    'verified_email_hash' => $registration->official_email_hash,
                ]);
            } catch (QueryException $exception) {
                if (! Str::contains(Str::lower($exception->getMessage()), 'verified_email_hash')) {
                    throw $exception;
                }

                $alreadyVerified = true;
                $registration->refresh();
            }
        }

        return response()->view('site.seed-summit.email-verified', array_merge($this->sharedViewData(), [
            'event' => $registration->event,
            'alreadyVerified' => $alreadyVerified,
            'verificationCompleted' => true,
        ]))->withHeaders($this->privateHeaders());
    }

    private function event(): Event
    {
        return Event::query()
            ->where('slug', config('seed_summit.event_slug'))
            ->where('is_published', true)
            ->firstOrFail();
    }

    private function ensureSeedSummitRegistration(EventRegistration $registration): void
    {
        abort_unless($registration->event_slug === config('seed_summit.event_slug'), 404);
    }

    private function signedUrl(string $route, EventRegistration $registration): string
    {
        return URL::temporarySignedRoute(
            $route,
            now()->addMinutes(max(1, (int) config('seed_summit.receipt_link_minutes', 30))),
            ['locale' => $registration->locale, 'registration' => $registration],
        );
    }

    private function ensureReceiptSession(Request $request, EventRegistration $registration): void
    {
        $expiresAt = $request->session()->get($this->receiptSessionKey($registration));

        abort_unless(is_int($expiresAt) && $expiresAt >= now()->timestamp, 403);
    }

    private function receiptSessionKey(EventRegistration $registration): string
    {
        return 'seed_summit.receipts.'.$registration->public_id;
    }

    /** @return array<string, mixed> */
    private function formInput(Request $request): array
    {
        $encrypted = $request->session()->pull('seed_summit.encrypted_form_input');

        if (! is_string($encrypted) || $encrypted === '') {
            return [];
        }

        try {
            $decoded = json_decode(Crypt::decryptString($encrypted), true, flags: JSON_THROW_ON_ERROR);
        } catch (DecryptException|JsonException) {
            return [];
        }

        return is_array($decoded) ? $decoded : [];
    }

    /** @return array<string, mixed> */
    private function sharedViewData(): array
    {
        return [
            'siteSettings' => Setting::all()
                ->mapWithKeys(fn (Setting $setting): array => [$setting->key => $setting->value])
                ->all(),
            'locales' => config('locales.supported'),
            'locale' => app()->getLocale(),
            'countries' => config('seed_summit.countries'),
            'memberStates' => config('seed_summit.member_states'),
            'titles' => config('seed_summit.titles'),
            'genderOptions' => config('seed_summit.genders'),
            'capacityOptions' => config('seed_summit.delegation_capacities'),
            'dietaryOptions' => config('seed_summit.dietary_requirements'),
            'dataProtectionNotice' => config('seed_summit.data_protection_notice'),
        ];
    }

    /** @return array<string, string> */
    private function privateHeaders(): array
    {
        return [
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
            'Referrer-Policy' => 'no-referrer',
            'X-Robots-Tag' => 'noindex, nofollow, noarchive',
        ];
    }
}
