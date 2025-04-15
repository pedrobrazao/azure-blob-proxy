<?php

declare(strict_types=1);

namespace App\Exception;

use InvalidArgumentException;

abstract class InvalidHttpRequestException extends InvalidArgumentException
{
    private const CODE = 400;

    public function __construct(string $message, int $code = self::CODE)
    {
        parent::__construct($message, $code);
    }
}
