<?php

declare(strict_types=1);

namespace App\Validator;

final class RequiredArgumentValidator implements ValidatorInterface
{
    use ValidatorTrait;

    public const MISSING_ARGUMENT_MESSAGE = 'Required missing argument in query string: ';

    public function __construct(
        private readonly string $name, 
        private readonly array $args
        ) {
        $this->validate($name, $args);
    }

    private function  validate(string $name, array $args): void
    {
        if ('' === false === array_key_exists($name, $args)) {
            $this->valid = false;
            $this->error = self::MISSING_ARGUMENT_MESSAGE . $name;
        }
    }
}
