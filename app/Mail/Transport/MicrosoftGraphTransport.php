<?php

namespace App\Mail\Transport;

use App\Services\Mail\MicrosoftGraphMailException;
use App\Services\Mail\MicrosoftGraphMailService;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;
use Throwable;

final class MicrosoftGraphTransport extends AbstractTransport
{
    public const MAX_DIRECT_ATTACHMENT_BYTES = 3_000_000;

    public function __construct(private readonly MicrosoftGraphMailService $graph)
    {
        parent::__construct();
    }

    public function __toString(): string
    {
        return 'graph';
    }

    protected function doSend(SentMessage $message): void
    {
        $email = $message->getOriginalMessage();

        if (! $email instanceof Email) {
            throw new TransportException(
                'Microsoft Graph transport requires a Symfony Email message.',
            );
        }

        try {
            $this->graph->send($this->payload($email));
        } catch (TransportException $exception) {
            throw $exception;
        } catch (MicrosoftGraphMailException $exception) {
            throw new TransportException($exception->getMessage(), 0, $exception);
        } catch (Throwable $exception) {
            throw new TransportException('Microsoft Graph could not send the email.', 0, $exception);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Email $email): array
    {
        $this->assertConfiguredSender($email);

        $html = $this->bodyToString($email->getHtmlBody());
        $text = $this->bodyToString($email->getTextBody());
        $body = $html ?? $text ?? '';
        $message = [
            'subject' => $email->getSubject() ?? '',
            'body' => [
                'contentType' => $html !== null ? 'HTML' : 'Text',
                'content' => $body,
            ],
            'toRecipients' => $this->recipients($email->getTo()),
        ];

        if ($email->getCc() !== []) {
            $message['ccRecipients'] = $this->recipients($email->getCc());
        }

        if ($email->getBcc() !== []) {
            $message['bccRecipients'] = $this->recipients($email->getBcc());
        }

        if ($email->getReplyTo() !== []) {
            $message['replyTo'] = $this->recipients($email->getReplyTo());
        }

        $attachments = $this->attachments($email->getAttachments());

        if ($attachments !== []) {
            $message['attachments'] = $attachments;
        }

        return $message;
    }

    private function assertConfiguredSender(Email $email): void
    {
        $configuredSender = (string) config('services.microsoft_graph.from_address');

        foreach ($email->getFrom() as $from) {
            if ($configuredSender === ''
                || strcasecmp($from->getAddress(), $configuredSender) !== 0) {
                throw new TransportException(
                    'The email From address must match the configured Microsoft Graph sender mailbox.',
                );
            }
        }
    }

    /**
     * @param  Address[]  $addresses
     * @return array<int, array<string, array<string, string>>>
     */
    private function recipients(array $addresses): array
    {
        return array_map(static function (Address $address): array {
            $emailAddress = ['address' => $address->getAddress()];

            if ($address->getName() !== '') {
                $emailAddress['name'] = $address->getName();
            }

            return ['emailAddress' => $emailAddress];
        }, $addresses);
    }

    /**
     * @param  DataPart[]  $parts
     * @return array<int, array<string, mixed>>
     */
    private function attachments(array $parts): array
    {
        $attachments = [];
        $totalBytes = 0;

        foreach ($parts as $part) {
            $body = $this->bodyToString($part->getBody()) ?? '';
            $totalBytes += strlen($body);

            if ($totalBytes >= self::MAX_DIRECT_ATTACHMENT_BYTES) {
                throw new TransportException(
                    'Microsoft Graph JSON attachments must total less than 3 MB. Use a draft and upload session for larger attachments.',
                );
            }

            $attachments[] = $this->attachment($part, $body);
        }

        return $attachments;
    }

    /**
     * @return array<string, mixed>
     */
    private function attachment(DataPart $part, string $body): array
    {
        $inline = $part->getDisposition() === 'inline';
        $attachment = [
            '@odata.type' => '#microsoft.graph.fileAttachment',
            'name' => $part->getFilename() ?? 'attachment',
            'contentType' => $part->getContentType(),
            'contentBytes' => base64_encode($body),
            'isInline' => $inline,
        ];

        if ($inline) {
            $attachment['contentId'] = $part->getContentId();
        }

        return $attachment;
    }

    private function bodyToString(mixed $body): ?string
    {
        if ($body === null) {
            return null;
        }

        if (is_resource($body)) {
            $contents = stream_get_contents($body);

            return $contents === false ? null : $contents;
        }

        return (string) $body;
    }
}
