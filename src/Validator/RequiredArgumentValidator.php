<?php

declare(strict_types=1);

namespace App\Validator;

final class RequiredArgumentValidator extends AbstractValidator
{
    public const MISSING_ARGUMENT_MESSAGE = 'Required missing argument in query string: ';

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
            || false === array_key_exists($value, $context)
        ) {
            $this->error = self::MISSING_ARGUMENT_MESSAGE . $value;

            return $this;
        }

        $this->valid = true;
        $this->error = null;

        return $this;
    }
}
