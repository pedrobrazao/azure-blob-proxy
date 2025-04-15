<?php

declare(strict_types=1);

namespace App\Exception;

final class InvalidBlobException extends InvalidHttpRequestException
{
    private const MESSAGE = 'Invalid blob name.';

    public function __construct(string $message = self::MESSAGE)
    {
        parent::__construct($message);
    }
}
