<?php

declare(strict_types=1);

namespace App\Exception;

final class MissingRequiredArgumentException extends InvalidHttpRequestException
{
    private const MESSAGE = 'One or more required argument is missing.';

    public function __construct(string $message = self::MESSAGE)
    {
        parent::__construct($message);
    }
}
