<?php

declare(strict_types=1);

namespace App\Validator;

final class BlobNameValidator extends AbstractValidator
{
    public const INVALID_NAME_MESSAGE = 'The blob name is invalid.';

    /**
     * @param scalar|object $value
     * @param array<string, string> $context
     */
    public function validate($value, array $context = []): self
    {
        parent::validate($value);

        if (
            false === is_string($value)
            || '' === $value
            || 254 < strlen($value)
        ) {
            $this->error = self::INVALID_NAME_MESSAGE;

            return $this;
        }

        $this->valid = true;
        $this->error = null;

        return $this;
    }
}
