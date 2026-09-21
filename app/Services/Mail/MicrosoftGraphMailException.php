<?php

namespace App\Services\Mail;

use RuntimeException;
use Throwable;

final class MicrosoftGraphMailException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly ?int $httpStatus = null,
        private readonly ?string $requestId = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function httpStatus(): ?int
    {
        return $this->httpStatus;
    }

    public function requestId(): ?string
    {
        return $this->requestId;
    }
}
