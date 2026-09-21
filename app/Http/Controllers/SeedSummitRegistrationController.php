<?php

namespace App\Http\Controllers;

use App\Actions\RegisterSeedSummitDelegate;
use App\Http\Requests\StoreEventRegistrationRequest;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Setting;
use App\Services\Mail\SeedSummitRegistrationMailDispatcher;
use App\Support\SeedSummitRegistrationPdf;
use App\Support\SeedSummitRegistrationSummary;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\URL;
use JsonException;

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
        SeedSummitRegistrationMailDispatcher $mailDispatcher,
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

        $mailDispatcher->queueAcknowledgement($registration);

        return redirect()->to($this->signedUrl('seed-summit.registrations.show', $registration));
    }

    public function show(
        string $locale,
        EventRegistration $registration,
        Request $request,
        SeedSummitRegistrationSummary $summary,
        SeedSummitRegistrationMailDispatcher $mailDispatcher,
    ): Response {
        $this->ensureSeedSummitRegistration($registration);
        $this->ensureReceiptSession($request, $registration);

        return response()->view('site.seed-summit.confirmation', array_merge($this->sharedViewData(), [
            'event' => $registration->event,
            'registration' => $registration,
            'summarySections' => $summary->for($registration),
            'pdfUrl' => $this->signedUrl('seed-summit.registrations.pdf', $registration),
            'emailStatus' => [
                'status' => $registration->confirmation_email_status,
                'message' => $request->session()->pull('seed_summit.confirmation_email_message'),
            ],
            'canRetryEmail' => $mailDispatcher->canQueueAcknowledgement($registration),
            'resendEmailUrl' => $this->signedUrl(
                'seed-summit.registrations.resend-confirmation',
                $registration,
            ),
        ]))->withHeaders($this->privateHeaders());
    }

    public function resendConfirmation(
        string $locale,
        EventRegistration $registration,
        Request $request,
        SeedSummitRegistrationMailDispatcher $mailDispatcher,
    ): RedirectResponse {
        $this->ensureSeedSummitRegistration($registration);
        $this->ensureReceiptSession($request, $registration);

        $status = $mailDispatcher->queueAcknowledgement($registration);
        $message = match ($status) {
            EventRegistration::EMAIL_SENT => 'The verification email has already been sent.',
            EventRegistration::EMAIL_FAILED => 'The verification email could not be queued. Please try again shortly.',
            default => 'The verification email is queued for delivery to the official email address.',
        };

        return redirect()
            ->to($this->signedUrl('seed-summit.registrations.show', $registration))
            ->with('seed_summit.confirmation_email_message', $message);
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

    public function showEmailVerification(
        string $locale,
        EventRegistration $registration,
        SeedSummitRegistrationMailDispatcher $mailDispatcher,
    ): Response {
        $this->ensureSeedSummitRegistration($registration);

        return response()->view('site.seed-summit.email-verified', array_merge($this->sharedViewData(), [
            'event' => $registration->event,
            'alreadyVerified' => $registration->hasVerifiedOfficialEmail(),
            'verificationSucceeded' => $registration->hasVerifiedOfficialEmail(),
            'verificationCompleted' => false,
            'receiptEmailStatus' => $registration->receipt_email_status,
            'canRetryReceiptEmail' => $mailDispatcher->canQueueReceipt($registration),
        ]))->withHeaders($this->privateHeaders());
    }

    public function verifyEmail(
        string $locale,
        EventRegistration $registration,
        SeedSummitRegistrationMailDispatcher $mailDispatcher,
    ): Response {
        $this->ensureSeedSummitRegistration($registration);
        $alreadyVerified = $registration->hasVerifiedOfficialEmail();

        if ($registration->official_email_verified_at === null) {
            try {
                $registration->update([
                    'official_email_verified_at' => now(),
                    'verified_email_hash' => $registration->official_email_hash,
                ]);
            } catch (QueryException $exception) {
                if (! $this->isVerifiedEmailConflict($exception, $registration)) {
                    throw $exception;
                }

                $alreadyVerified = true;
                $registration->refresh();
            }
        }

        $registration->refresh();
        $verificationSucceeded = $registration->hasVerifiedOfficialEmail();
        $receiptEmailStatus = $verificationSucceeded
            ? $mailDispatcher->queueReceipt($registration)
            : $registration->receipt_email_status;
        $registration->refresh();

        return response()->view('site.seed-summit.email-verified', array_merge($this->sharedViewData(), [
            'event' => $registration->event,
            'alreadyVerified' => $alreadyVerified,
            'verificationSucceeded' => $verificationSucceeded,
            'verificationCompleted' => true,
            'receiptEmailStatus' => $receiptEmailStatus,
            'canRetryReceiptEmail' => $mailDispatcher->canQueueReceipt($registration),
        ]))->withHeaders($this->privateHeaders());
    }

    private function isVerifiedEmailConflict(
        QueryException $exception,
        EventRegistration $registration,
    ): bool {
        $sqlState = (string) ($exception->errorInfo[0] ?? $exception->getCode());

        if (! in_array($sqlState, ['23000', '23505'], true)) {
            return false;
        }

        return EventRegistration::query()
            ->where('event_id', $registration->event_id)
            ->where('verified_email_hash', $registration->official_email_hash)
            ->whereKeyNot($registration->getKey())
            ->exists();
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
