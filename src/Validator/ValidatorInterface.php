<?php

namespace App\Validator;

interface ValidatorInterface
{   
    public function isValid(): bool;
    public function getError(): ?string;
}