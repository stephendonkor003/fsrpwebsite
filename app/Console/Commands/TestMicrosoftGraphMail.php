<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

final class TestMicrosoftGraphMail extends Command
{
    protected $signature = 'graph-mail:test {recipient : The email address that should receive the test}';

    protected $description = 'Send a synchronous test email through Microsoft Graph';

    public function handle(): int
    {
        $recipient = (string) $this->argument('recipient');

        if (config('mail.default') !== 'graph') {
            $this->error(
                'The default mailer is not Microsoft Graph. Set MAIL_MAILER=graph, rebuild cached configuration, and restart queue workers.',
            );

            return self::FAILURE;
        }

        if (filter_var($recipient, FILTER_VALIDATE_EMAIL) === false) {
            $this->error('The recipient must be a valid email address.');

            return self::INVALID;
        }

        try {
            Mail::html(
                '<p>Microsoft Graph email delivery is configured for '.e((string) config('app.name')).'.</p>',
                function ($message) use ($recipient): void {
                    $message
                        ->to($recipient)
                        ->subject(config('app.name').' Microsoft Graph mail test');
                },
            );
        } catch (Throwable $exception) {
            report($exception);
            $this->error(
                'Microsoft Graph did not accept the test email. Check the application log for sanitized diagnostics.',
            );

            return self::FAILURE;
        }

        $this->info('Microsoft Graph accepted the test email request (HTTP 202).');

        if (config('queue.default') !== 'sync') {
            $this->warn('Queued application mail still requires a worker consuming the default queue.');
        }

        return self::SUCCESS;
    }
}
