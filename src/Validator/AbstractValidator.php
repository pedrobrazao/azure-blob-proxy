<?php

declare(strict_types=1);

namespace App\Validator;

abstract class 
AbstractValidator implements ValidatorInterface
{
    public const INCOMPLTE_VALIDATION_MESSAGE = 'The validation didn\'t complete.';

    protected bool $valid = false;
    protected ?string $error = self::INCOMPLTE_VALIDATION_MESSAGE;

    public function validate($value, array $context = []): self
    {
        $this->valid = false;
        $this->error = self::INCOMPLTE_VALIDATION_MESSAGE;

        return $this;
    }

    public function isValid(): bool
    {
        return $this->valid;
    }

    public function getError(): ?string
    {
        return $this->error;
    }
}
