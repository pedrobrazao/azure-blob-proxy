<?php

declare(strict_types=1);

namespace App\Exception;

use InvalidArgumentException;

final class InvalidUploadException extends InvalidArgumentException
{
    private const MESSAGE = 'Invalid or missing file upload.';
    private const CODE = 400;

    public function __construct(string $message = self::MESSAGE, int $code = self::CODE)
    {
        parent::__construct($message, $code);
    }
}
