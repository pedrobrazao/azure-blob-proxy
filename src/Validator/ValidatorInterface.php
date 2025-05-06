<?php

declare(strict_types=1);

namespace App\Validator;

interface ValidatorInterface
{
    /**
     * @param scalar|object $value
     * @param array<string, string> $context
     */
    public function validate($value, array $context = []): self;
    public function isValid(): bool;
    public function getError(): ?string;
}
