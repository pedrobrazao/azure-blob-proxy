<?php

declare(strict_types=1);

namespace App\Exception;

final class InvalidUploadException extends InvalidHttpRequestException
{
    private const MESSAGE = 'Invalid or missing file upload.';

    public function __construct(string $message = self::MESSAGE)
    {
        parent::__construct($message);
    }
}
