<?php

declare(strict_types=1);

namespace App\Exception;

use InvalidArgumentException;

final class InvalidContainerException extends InvalidArgumentException
{
    private const MESSAGE = 'Invalid container name.';
    private const CODE = 400;

    public function __construct(string $message = self::MESSAGE, int $code = self::CODE)
    {
        parent::__construct($message, $code);
    }
}
