<?php

declare(strict_types=1);

namespace App\Exception;

final class InvalidOperationException extends InvalidHttpRequestException
{
    private const MESSAGE = 'Invalid or missing operation.';

    public function __construct(string $message = self::MESSAGE)
    {
        parent::__construct($message);
    }
}
