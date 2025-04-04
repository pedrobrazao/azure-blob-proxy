<?php

declare(strict_types=1);

namespace App\Validator;

final class BlobNameValidator implements ValidatorInterface
{
    use ValidatorTrait;

    public const INVALID_NAME_MESSAGE = 'The blob name is invalid.';

    public function __construct(private readonly string $value)
    {
        $this->validate($value);
    }

    private function  validate(string $value): void
    {
        if ('' === trim($value) || 254 < strlen($value)) {
            $this->valid = false;
            $this->error = self::INVALID_NAME_MESSAGE;
        }
    }
}
