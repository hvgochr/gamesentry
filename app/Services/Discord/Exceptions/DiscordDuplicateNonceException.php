<?php

declare(strict_types=1);

namespace App\Services\Discord\Exceptions;

use RuntimeException;

class DiscordDuplicateNonceException extends RuntimeException
{
    public function __construct(string $message = 'This Discord message has already been delivered.')
    {
        parent::__construct($message);
    }
}
