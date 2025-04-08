<?php

declare(strict_types=1);

namespace App\Exception;

use InvalidArgumentException;

final class InvalidBlobException extends InvalidArgumentException
{
    private const MESSAGE = 'Invalid blob name.';
    private const CODE = 400;

}
