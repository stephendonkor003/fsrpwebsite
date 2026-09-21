<?php

namespace App\Services\Mail;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use JsonException;
use Throwable;

final class MicrosoftGraphMailService
{
    public const MAX_REQUEST_BYTES = 4_000_000;

    /** @var array<string, true> */
    private static array $certificateExpiryWarnings = [];

    /**
     * @param  array<string, mixed>  $message
     */
    public function send(array $message): int
    {
        try {
            $requestPayload = $this->requestPayload($message);
            $this->assertRequestSize($requestPayload);
            $credentials = $this->validatedCredentials();
            $tokenCacheKey = $this->tokenCacheKey($credentials['certificate_fingerprint']);
            $response = $this->sendRequest(
                $requestPayload,
                $this->accessToken($credentials, $tokenCacheKey),
            );

            if ($response->status() === 401) {
                Cache::forget($tokenCacheKey);
                $response = $this->sendRequest(
                    $requestPayload,
                    $this->accessToken($credentials, $tokenCacheKey),
                );
            }

            if ($response->status() !== 202) {
                $this->logFailure('Microsoft Graph rejected an email request.', $response);

                throw new MicrosoftGraphMailException(
                    "Microsoft Graph sendMail returned HTTP {$response->status()}.",
                    $response->status(),
                    $this->requestId($response),
                );
            }

            Log::info('Microsoft Graph accepted an email request.', [
                'to_count' => count((array) ($message['toRecipients'] ?? [])),
                'cc_count' => count((array) ($message['ccRecipients'] ?? [])),
                'bcc_count' => count((array) ($message['bccRecipients'] ?? [])),
                'attachment_count' => count((array) ($message['attachments'] ?? [])),
                'request_id' => $this->requestId($response),
            ]);

            return 202;
        } catch (MicrosoftGraphMailException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::error('Microsoft Graph email delivery failed.', [
                'exception' => $exception::class,
            ]);

            throw new MicrosoftGraphMailException(
                'Microsoft Graph email delivery failed.',
                previous: $exception,
            );
        }
    }

    /**
     * @param  array{client_id: string, certificate_der: string, certificate_fingerprint: string, private_key: \OpenSSLAsymmetricKey}|null  $credentials
     */
    public function clientAssertion(?array $credentials = null): string
    {
        $credentials ??= $this->validatedCredentials();
        $now = $this->currentTimestamp();
        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT',
            'x5t' => $this->base64UrlEncode(sha1($credentials['certificate_der'], true)),
            'x5t#S256' => $this->base64UrlEncode(hash('sha256', $credentials['certificate_der'], true)),
        ];
        $claims = [
            'aud' => $this->tokenEndpoint(),
            'iss' => $credentials['client_id'],
            'sub' => $credentials['client_id'],
            'jti' => bin2hex(random_bytes(16)),
            'iat' => $now,
            'nbf' => $now - 5,
            'exp' => $now + 300,
        ];
        $unsigned = $this->base64UrlEncode($this->json($header))
            .'.'.$this->base64UrlEncode($this->json($claims));
        $signature = '';

        if (! openssl_sign($unsigned, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256)) {
            throw new MicrosoftGraphMailException('Microsoft Graph client assertion could not be signed.');
        }

        return $unsigned.'.'.$this->base64UrlEncode($signature);
    }

    /**
     * @param  array{client_id: string, certificate_der: string, certificate_fingerprint: string, private_key: \OpenSSLAsymmetricKey}  $credentials
     */
    private function accessToken(array $credentials, string $cacheKey): string
    {
        if (is_string($token = $this->cachedAccessToken($cacheKey))) {
            return $token;
        }

        return Cache::lock("{$cacheKey}:lock", 15)->block(10, function () use ($credentials, $cacheKey): string {
            if (is_string($token = $this->cachedAccessToken($cacheKey))) {
                return $token;
            }

            $response = Http::asForm()
                ->acceptJson()
                ->connectTimeout($this->connectTimeout())
                ->timeout($this->timeout())
                ->post($this->tokenEndpoint(), [
                    'grant_type' => 'client_credentials',
                    'client_id' => config('services.microsoft_graph.client_id'),
                    'scope' => config('services.microsoft_graph.scope'),
                    'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
                    'client_assertion' => $this->clientAssertion($credentials),
                ]);

            if (! $response->successful()) {
                $this->logFailure('Microsoft OAuth token request failed.', $response);

                throw new MicrosoftGraphMailException(
                    "Microsoft OAuth token endpoint returned HTTP {$response->status()}.",
                    $response->status(),
                    $this->requestId($response),
                );
            }

            $token = $response->json('access_token');
            $expiresIn = max(1, (int) $response->json('expires_in', 3600));

            if (! is_string($token) || $token === '') {
                throw new MicrosoftGraphMailException(
                    'Microsoft OAuth token response did not contain an access token.',
                );
            }

            $ttl = max(1, $expiresIn - min(300, max(60, intdiv($expiresIn, 10))));
            Cache::put($cacheKey, Crypt::encryptString($token), now()->addSeconds($ttl));

            return $token;
        });
    }

    /**
     * @param  array<string, mixed>  $requestPayload
     */
    private function sendRequest(array $requestPayload, string $token): Response
    {
        $sender = rawurlencode((string) config('services.microsoft_graph.from_address'));
        $baseUrl = rtrim((string) config('services.microsoft_graph.base_url'), '/');

        return Http::withToken($token)
            ->acceptJson()
            ->asJson()
            ->connectTimeout($this->connectTimeout())
            ->timeout($this->timeout())
            ->post("{$baseUrl}/users/{$sender}/sendMail", $requestPayload);
    }

    /**
     * @param  array<string, mixed>  $message
     * @return array<string, mixed>
     */
    private function requestPayload(array $message): array
    {
        return [
            'message' => $message,
            'saveToSentItems' => true,
        ];
    }

    /**
     * @param  array<string, mixed>  $requestPayload
     */
    private function assertRequestSize(array $requestPayload): void
    {
        $encoded = json_encode($requestPayload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        if (strlen($encoded) >= self::MAX_REQUEST_BYTES) {
            throw new MicrosoftGraphMailException(
                'Microsoft Graph email request must be smaller than 4 MB. Use a draft and upload session for larger attachments.',
            );
        }
    }

    /**
     * @return array{client_id: string, certificate_der: string, certificate_fingerprint: string, private_key: \OpenSSLAsymmetricKey}
     */
    private function validatedCredentials(): array
    {
        foreach (['tenant_id', 'client_id', 'certificate_path', 'private_key_path', 'from_address'] as $key) {
            if (blank(config("services.microsoft_graph.{$key}"))) {
                throw new MicrosoftGraphMailException(
                    "Microsoft Graph mail is not configured. Missing: {$key}.",
                );
            }
        }

        $senderAddress = (string) config('services.microsoft_graph.from_address');
        $mailFromAddress = (string) config('mail.from.address');

        if (! filter_var($senderAddress, FILTER_VALIDATE_EMAIL)) {
            throw new MicrosoftGraphMailException('Microsoft Graph sender address is invalid.');
        }

        if (! filter_var($mailFromAddress, FILTER_VALIDATE_EMAIL)) {
            throw new MicrosoftGraphMailException('The application mail sender address is invalid.');
        }

        if (strcasecmp($senderAddress, $mailFromAddress) !== 0) {
            throw new MicrosoftGraphMailException(
                'Microsoft Graph sender address must match the application mail sender address.',
            );
        }

        $certificate = $this->readCredentialFile(
            (string) config('services.microsoft_graph.certificate_path'),
            'certificate',
        );
        $privateKeyPem = $this->readCredentialFile(
            (string) config('services.microsoft_graph.private_key_path'),
            'private key',
        );
        $x509 = @openssl_x509_read($certificate);

        if ($x509 === false) {
            throw new MicrosoftGraphMailException('Microsoft Graph certificate could not be parsed.');
        }

        $privateKey = @openssl_pkey_get_private($privateKeyPem);

        if ($privateKey === false) {
            throw new MicrosoftGraphMailException('Microsoft Graph private key could not be parsed.');
        }

        if (! openssl_x509_check_private_key($x509, $privateKey)) {
            throw new MicrosoftGraphMailException(
                'Microsoft Graph certificate and private key do not match.',
            );
        }

        $privateKeyDetails = openssl_pkey_get_details($privateKey);

        if (! is_array($privateKeyDetails) || ($privateKeyDetails['type'] ?? null) !== OPENSSL_KEYTYPE_RSA) {
            throw new MicrosoftGraphMailException('Microsoft Graph certificate must use an RSA private key.');
        }

        if (($privateKeyDetails['bits'] ?? 0) < 2048) {
            throw new MicrosoftGraphMailException(
                'Microsoft Graph RSA private key must be at least 2048 bits.',
            );
        }

        $certificateDetails = @openssl_x509_parse($x509, false);

        if (! is_array($certificateDetails)
            || ! isset($certificateDetails['validFrom_time_t'], $certificateDetails['validTo_time_t'])) {
            throw new MicrosoftGraphMailException(
                'Microsoft Graph certificate validity period could not be determined.',
            );
        }

        $now = $this->currentTimestamp();
        $validFrom = (int) $certificateDetails['validFrom_time_t'];
        $validTo = (int) $certificateDetails['validTo_time_t'];

        if ($validFrom > $now) {
            throw new MicrosoftGraphMailException('Microsoft Graph certificate is not yet valid.');
        }

        if ($validTo <= $now) {
            throw new MicrosoftGraphMailException('Microsoft Graph certificate has expired.');
        }

        $exported = '';

        if (! openssl_x509_export($x509, $exported)) {
            throw new MicrosoftGraphMailException('Microsoft Graph certificate could not be processed.');
        }

        $encoded = preg_replace('/-----[^-]+-----|\s+/', '', $exported);
        $der = is_string($encoded) ? base64_decode($encoded, true) : false;

        if (! is_string($der) || $der === '') {
            throw new MicrosoftGraphMailException('Microsoft Graph certificate could not be processed.');
        }

        $warningDays = max(
            0,
            (int) config('services.microsoft_graph.certificate_expiry_warning_days', 30),
        );
        $certificateFingerprint = hash('sha256', $der);

        if ($warningDays > 0
            && $validTo <= $now + ($warningDays * 86_400)
            && ! isset(self::$certificateExpiryWarnings[$certificateFingerprint])) {
            self::$certificateExpiryWarnings[$certificateFingerprint] = true;
            Log::warning('Microsoft Graph certificate is approaching expiry.', [
                'expires_at' => gmdate(DATE_ATOM, $validTo),
                'days_remaining' => max(0, (int) floor(($validTo - $now) / 86_400)),
            ]);
        }

        return [
            'client_id' => (string) config('services.microsoft_graph.client_id'),
            'certificate_der' => $der,
            'certificate_fingerprint' => $certificateFingerprint,
            'private_key' => $privateKey,
        ];
    }

    private function readCredentialFile(string $path, string $label): string
    {
        if (! is_file($path)) {
            throw new MicrosoftGraphMailException("Microsoft Graph {$label} file does not exist.");
        }

        if (! is_readable($path)) {
            throw new MicrosoftGraphMailException("Microsoft Graph {$label} file is not readable.");
        }

        $contents = @file_get_contents($path);

        if (! is_string($contents) || $contents === '') {
            throw new MicrosoftGraphMailException("Microsoft Graph {$label} file could not be read.");
        }

        return $contents;
    }

    protected function currentTimestamp(): int
    {
        return time();
    }

    private function cachedAccessToken(string $key): ?string
    {
        $encrypted = Cache::get($key);

        if (! is_string($encrypted) || $encrypted === '') {
            return null;
        }

        try {
            $token = Crypt::decryptString($encrypted);
        } catch (DecryptException) {
            Cache::forget($key);
            Log::warning('Discarded an unreadable cached Microsoft Graph access token.');

            return null;
        }

        if ($token === '') {
            Cache::forget($key);

            return null;
        }

        return $token;
    }

    private function tokenEndpoint(): string
    {
        $tenant = rawurlencode((string) config('services.microsoft_graph.tenant_id'));

        return "https://login.microsoftonline.com/{$tenant}/oauth2/v2.0/token";
    }

    private function tokenCacheKey(string $certificateFingerprint): string
    {
        return 'microsoft_graph:mail_token:'.hash('sha256', implode('|', [
            (string) config('services.microsoft_graph.tenant_id'),
            (string) config('services.microsoft_graph.client_id'),
            (string) config('services.microsoft_graph.from_address'),
            $certificateFingerprint,
        ]));
    }

    private function timeout(): int
    {
        return max(1, (int) config('services.microsoft_graph.timeout', 30));
    }

    private function connectTimeout(): int
    {
        return max(1, (int) config('services.microsoft_graph.connect_timeout', 10));
    }

    private function requestId(Response $response): ?string
    {
        return $this->sanitizedLogValue(
            $response->header('request-id')
                ?: $response->header('client-request-id')
                ?: $response->header('x-ms-request-id'),
        );
    }

    private function logFailure(string $message, Response $response): void
    {
        Log::error($message, [
            'status' => $response->status(),
            'error_code' => $this->sanitizedLogValue(
                $response->json('error.code') ?? $response->json('error'),
            ),
            'request_id' => $this->requestId($response),
        ]);
    }

    private function sanitizedLogValue(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        $sanitized = preg_replace('/[^A-Za-z0-9._:-]/', '', substr($value, 0, 128));

        return is_string($sanitized) && $sanitized !== '' ? $sanitized : null;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    /**
     * @param  array<string, mixed>  $value
     *
     * @throws JsonException
     */
    private function json(array $value): string
    {
        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
