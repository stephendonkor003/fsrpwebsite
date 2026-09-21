<?php

namespace Tests\Feature\Mail;

use App\Services\Mail\MicrosoftGraphMailException;
use App\Services\Mail\MicrosoftGraphMailService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

final class MicrosoftGraphMailTest extends TestCase
{
    private static string $certificatePath;

    private static string $privateKeyPath;

    private static string $mismatchedPrivateKeyPath;

    private static string $temporaryDirectory;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$temporaryDirectory = sys_get_temp_dir()
            .DIRECTORY_SEPARATOR.'fsrp-graph-mail-'.bin2hex(random_bytes(8));

        if (! mkdir(self::$temporaryDirectory, 0700) && ! is_dir(self::$temporaryDirectory)) {
            throw new RuntimeException('Unable to create the Graph mail test directory.');
        }

        self::$certificatePath = self::$temporaryDirectory.DIRECTORY_SEPARATOR.'certificate.pem';
        self::$privateKeyPath = self::$temporaryDirectory.DIRECTORY_SEPARATOR.'private-key.pem';
        self::$mismatchedPrivateKeyPath = self::$temporaryDirectory.DIRECTORY_SEPARATOR.'mismatched-key.pem';
        $opensslConfigurationPath = self::$temporaryDirectory.DIRECTORY_SEPARATOR.'openssl.cnf';
        $opensslConfiguration = <<<'INI'
[ req ]
distinguished_name = distinguished_name
prompt = no

[ distinguished_name ]
CN = graph-mail.test
INI;

        if (file_put_contents($opensslConfigurationPath, $opensslConfiguration) === false) {
            throw new RuntimeException('Unable to create the Graph mail test OpenSSL configuration.');
        }

        $options = [
            'config' => $opensslConfigurationPath,
            'digest_alg' => 'sha256',
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];
        $privateKey = openssl_pkey_new($options);
        $mismatchedPrivateKey = openssl_pkey_new($options);

        if ($privateKey === false || $mismatchedPrivateKey === false) {
            throw new RuntimeException('Unable to create the Graph mail test keys.');
        }

        $request = openssl_csr_new(['commonName' => 'graph-mail.test'], $privateKey, $options);
        $certificate = $request === false
            ? false
            : openssl_csr_sign($request, null, $privateKey, 1, $options);
        $certificatePem = '';
        $privateKeyPem = '';
        $mismatchedPrivateKeyPem = '';

        if ($certificate === false
            || ! openssl_x509_export($certificate, $certificatePem)
            || ! openssl_pkey_export($privateKey, $privateKeyPem, null, $options)
            || ! openssl_pkey_export($mismatchedPrivateKey, $mismatchedPrivateKeyPem, null, $options)
            || file_put_contents(self::$certificatePath, $certificatePem) === false
            || file_put_contents(self::$privateKeyPath, $privateKeyPem) === false
            || file_put_contents(self::$mismatchedPrivateKeyPath, $mismatchedPrivateKeyPem) === false) {
            throw new RuntimeException('Unable to create the Graph mail test credentials.');
        }

        unlink($opensslConfigurationPath);
    }

    public static function tearDownAfterClass(): void
    {
        foreach ([
            self::$certificatePath ?? null,
            self::$privateKeyPath ?? null,
            self::$mismatchedPrivateKeyPath ?? null,
        ] as $path) {
            if (is_string($path) && is_file($path)) {
                unlink($path);
            }
        }

        if (isset(self::$temporaryDirectory) && is_dir(self::$temporaryDirectory)) {
            rmdir(self::$temporaryDirectory);
        }

        parent::tearDownAfterClass();
    }

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.key' => 'base64:'.base64_encode(str_repeat('g', 32)),
            'cache.default' => 'array',
            'mail.default' => 'graph',
            'mail.from.address' => 'Noreply-AUCATTP@africanunion.org',
            'mail.from.name' => 'SEED SUBMIT',
            'queue.default' => 'sync',
            'services.microsoft_graph' => [
                'tenant_id' => '11111111-1111-1111-1111-111111111111',
                'client_id' => '22222222-2222-2222-2222-222222222222',
                'certificate_path' => self::$certificatePath,
                'private_key_path' => self::$privateKeyPath,
                'from_address' => 'Noreply-AUCATTP@africanunion.org',
                'scope' => 'https://graph.microsoft.com/.default',
                'base_url' => 'https://graph.microsoft.com/v1.0',
                'connect_timeout' => 1,
                'timeout' => 2,
                'certificate_expiry_warning_days' => 0,
            ],
        ]);

        Cache::clear();
        Mail::purge('graph');
        Http::preventStrayRequests();
    }

    public function test_graph_mailer_maps_supported_email_fields_and_uses_the_encoded_sender(): void
    {
        $this->fakeAcceptedGraphRequest();

        Mail::mailer('graph')->html('<p>Registration received.</p>', function ($message): void {
            $message
                ->to('delegate@example.test', 'Delegate Name')
                ->cc('coordinator@example.test', 'Coordinator')
                ->bcc('audit@example.test')
                ->replyTo('events@example.test', 'Events Team')
                ->subject('Seed Investment Summit registration')
                ->attachData('agenda', 'agenda.txt', ['mime' => 'text/plain']);
        });

        Http::assertSent(function (Request $request): bool {
            if ($request->url() !== 'https://graph.microsoft.com/v1.0/users/Noreply-AUCATTP%40africanunion.org/sendMail') {
                return false;
            }

            $message = $request->data()['message'] ?? [];

            return ($request->data()['saveToSentItems'] ?? null) === true
                && ($message['subject'] ?? null) === 'Seed Investment Summit registration'
                && ($message['body'] ?? null) === [
                    'contentType' => 'HTML',
                    'content' => '<p>Registration received.</p>',
                ]
                && ($message['toRecipients'][0]['emailAddress'] ?? null) === [
                    'address' => 'delegate@example.test',
                    'name' => 'Delegate Name',
                ]
                && ($message['ccRecipients'][0]['emailAddress']['name'] ?? null) === 'Coordinator'
                && ($message['bccRecipients'][0]['emailAddress']['address'] ?? null) === 'audit@example.test'
                && ($message['replyTo'][0]['emailAddress']['address'] ?? null) === 'events@example.test'
                && ($message['attachments'][0]['name'] ?? null) === 'agenda.txt'
                && ($message['attachments'][0]['contentBytes'] ?? null) === base64_encode('agenda');
        });
    }

    public function test_client_assertion_is_short_lived_rs256_and_token_request_has_no_secret(): void
    {
        $this->fakeAcceptedGraphRequest();
        $service = app(MicrosoftGraphMailService::class);
        $parts = explode('.', $service->clientAssertion());

        $this->assertCount(3, $parts);
        $header = json_decode($this->base64UrlDecode($parts[0]), true, flags: JSON_THROW_ON_ERROR);
        $claims = json_decode($this->base64UrlDecode($parts[1]), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('RS256', $header['alg']);
        $this->assertNotSame('', $header['x5t']);
        $this->assertNotSame('', $header['x5t#S256']);
        $this->assertSame(300, $claims['exp'] - $claims['iat']);
        $this->assertSame('22222222-2222-2222-2222-222222222222', $claims['iss']);
        $this->assertSame($claims['iss'], $claims['sub']);

        $service->send(['subject' => 'Assertion test']);

        Http::assertSent(function (Request $request): bool {
            return str_contains($request->url(), 'login.microsoftonline.com')
                && ($request->data()['grant_type'] ?? null) === 'client_credentials'
                && isset($request->data()['client_assertion'])
                && ! array_key_exists('client_secret', $request->data());
        });
    }

    public function test_access_token_is_encrypted_cached_and_reused(): void
    {
        $tokenRequests = 0;
        $graphRequests = 0;
        Http::fake(function (Request $request) use (&$tokenRequests, &$graphRequests) {
            if (str_contains($request->url(), 'login.microsoftonline.com')) {
                $tokenRequests++;

                return Http::response(['access_token' => 'sensitive-token', 'expires_in' => 3600]);
            }

            $graphRequests++;

            return Http::response(status: 202);
        });

        $service = app(MicrosoftGraphMailService::class);
        $service->send(['subject' => 'First']);
        $service->send(['subject' => 'Second']);

        $this->assertSame(1, $tokenRequests);
        $this->assertSame(2, $graphRequests);
        $encrypted = Cache::get($this->tokenCacheKey());
        $this->assertIsString($encrypted);
        $this->assertNotSame('sensitive-token', $encrypted);
        $this->assertSame('sensitive-token', Crypt::decryptString($encrypted));
    }

    public function test_http_401_forgets_the_token_and_retries_once(): void
    {
        $tokenRequests = 0;
        $graphRequests = 0;
        Http::fake(function (Request $request) use (&$tokenRequests, &$graphRequests) {
            if (str_contains($request->url(), 'login.microsoftonline.com')) {
                $tokenRequests++;

                return Http::response([
                    'access_token' => "token-{$tokenRequests}",
                    'expires_in' => 3600,
                ]);
            }

            $graphRequests++;

            return Http::response(status: $graphRequests === 1 ? 401 : 202);
        });

        $status = app(MicrosoftGraphMailService::class)->send(['subject' => 'Retry test']);

        $this->assertSame(202, $status);
        $this->assertSame(2, $tokenRequests);
        $this->assertSame(2, $graphRequests);
    }

    #[DataProvider('missingConfigurationProvider')]
    public function test_missing_required_configuration_fails_before_an_http_request(string $key): void
    {
        config()->set("services.microsoft_graph.{$key}", null);
        Http::fake();

        try {
            app(MicrosoftGraphMailService::class)->send([]);
            $this->fail('Missing Graph configuration should prevent delivery.');
        } catch (MicrosoftGraphMailException $exception) {
            $this->assertSame(
                "Microsoft Graph mail is not configured. Missing: {$key}.",
                $exception->getMessage(),
            );
        }

        Http::assertNothingSent();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function missingConfigurationProvider(): array
    {
        return [
            'tenant ID' => ['tenant_id'],
            'client ID' => ['client_id'],
            'certificate' => ['certificate_path'],
            'private key' => ['private_key_path'],
            'sender' => ['from_address'],
        ];
    }

    public function test_mismatched_certificate_and_private_key_are_rejected(): void
    {
        config()->set('services.microsoft_graph.private_key_path', self::$mismatchedPrivateKeyPath);
        Http::fake();

        $this->expectException(MicrosoftGraphMailException::class);
        $this->expectExceptionMessage('Microsoft Graph certificate and private key do not match.');

        app(MicrosoftGraphMailService::class)->send([]);
    }

    public function test_graph_rejection_reports_only_sanitized_metadata(): void
    {
        Log::spy();
        Http::fake([
            'login.microsoftonline.com/*' => Http::response([
                'access_token' => 'test-access-token',
                'expires_in' => 3600,
            ]),
            'graph.microsoft.com/*' => Http::response(
                ['error' => ['code' => 'ErrorAccessDenied<script>', 'message' => 'Sensitive body']],
                403,
                ['request-id' => 'request<script>'],
            ),
        ]);

        try {
            app(MicrosoftGraphMailService::class)->send([
                'subject' => 'Sensitive subject',
                'toRecipients' => [['emailAddress' => ['address' => 'private@example.test']]],
            ]);
            $this->fail('A Graph rejection should fail delivery.');
        } catch (MicrosoftGraphMailException $exception) {
            $this->assertSame(403, $exception->httpStatus());
            $this->assertSame('requestscript', $exception->requestId());
        }

        Log::shouldHaveReceived('error')->once()->with(
            'Microsoft Graph rejected an email request.',
            Mockery::on(fn (array $context): bool => $context === [
                'status' => 403,
                'error_code' => 'ErrorAccessDeniedscript',
                'request_id' => 'requestscript',
            ]),
        );
    }

    public function test_transport_rejects_attachments_at_the_direct_upload_limit(): void
    {
        Http::fake();

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('Microsoft Graph JSON attachments must total less than 3 MB.');

        Mail::mailer('graph')->html('<p>Attachment test.</p>', function ($message): void {
            $message
                ->to('delegate@example.test')
                ->subject('Attachment test')
                ->attachData(
                    str_repeat('x', 3_000_000),
                    'oversized.txt',
                    ['mime' => 'text/plain'],
                );
        });
    }

    public function test_graph_mail_command_validates_the_recipient_without_network_access(): void
    {
        Http::fake();

        $this->artisan('graph-mail:test', ['recipient' => 'not-an-email'])
            ->expectsOutput('The recipient must be a valid email address.')
            ->assertExitCode(2);

        Http::assertNothingSent();
    }

    private function fakeAcceptedGraphRequest(): void
    {
        Http::fake([
            'login.microsoftonline.com/*' => Http::response([
                'access_token' => 'test-access-token',
                'expires_in' => 3600,
            ]),
            'graph.microsoft.com/*' => Http::response(null, 202),
        ]);
    }

    private function tokenCacheKey(): string
    {
        return 'microsoft_graph:mail_token:'.hash('sha256', implode('|', [
            '11111111-1111-1111-1111-111111111111',
            '22222222-2222-2222-2222-222222222222',
            'Noreply-AUCATTP@africanunion.org',
            $this->certificateFingerprint(),
        ]));
    }

    private function certificateFingerprint(): string
    {
        $certificate = openssl_x509_read((string) file_get_contents(self::$certificatePath));

        if ($certificate === false || ! openssl_x509_export($certificate, $certificatePem)) {
            throw new RuntimeException('Unable to fingerprint the Graph mail test certificate.');
        }

        $encoded = preg_replace('/-----[^-]+-----|\s+/', '', $certificatePem);
        $der = is_string($encoded) ? base64_decode($encoded, true) : false;

        if (! is_string($der)) {
            throw new RuntimeException('Unable to decode the Graph mail test certificate.');
        }

        return hash('sha256', $der);
    }

    private function base64UrlDecode(string $value): string
    {
        $padding = (4 - strlen($value) % 4) % 4;
        $decoded = base64_decode(
            strtr($value, '-_', '+/').str_repeat('=', $padding),
            true,
        );

        return is_string($decoded) ? $decoded : '';
    }
}
