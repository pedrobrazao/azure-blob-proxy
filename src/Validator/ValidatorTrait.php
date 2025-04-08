<?php

declare(strict_types=1);

namespace App\Validator;

trait ValidatorTrait
{
    private bool $valid = true;
    private ?string $error = null;

    public function isValid(): bool
    {
        return $this->valid;
    }

    public function getError(): ?string
    {
        return $this->error;
    }
}
