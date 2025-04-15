<?php

declare(strict_types=1);

namespace App\Exception;

final class InvalidContainerException extends InvalidHttpRequestException
{
    private const MESSAGE = 'Invalid container name.';

    public function __construct(string $message = self::MESSAGE)
    {
        parent::__construct($message);
    }
}
