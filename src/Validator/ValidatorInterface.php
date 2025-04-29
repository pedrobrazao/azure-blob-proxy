<?php

declare(strict_types=1);

namespace App\Validator;

interface ValidatorInterface
{
    public function validate($value, array $context = []): self;
    public function isValid(): bool;
    public function getError(): ?string;
}
