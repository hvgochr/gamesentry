<?php

declare(strict_types=1);

namespace App\Services\Riot\Exceptions;

use RuntimeException;

class RiotRateLimitException extends RuntimeException
{
    public function __construct(
        string $message = 'The Riot limit has been reached. Please try again in a few moments.',
        private readonly ?int $retryAfterSeconds = null,
    ) {
        parent::__construct($message);
    }

    public function retryAfterSeconds(): ?int
    {
        return $this->retryAfterSeconds;
    }
}
