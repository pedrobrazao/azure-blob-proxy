<?php

declare(strict_types=1);

namespace App\Validator;

final class ContainerNameValidator implements ValidatorInterface
{
    use ValidatorTrait;
    
    /**
     * Container name MUST be:
     *  - 3 to 63 Characters;
     * - Starts With Letter or Number;
     * - Contains Letters, Numbers, and Dash (-);
     * - Every Dash (-) Must Be Immediately Preceded and Followed by a Letter or Number
     */
    public const NAME_PATTERN = '/^(?=.{3,63}$)[a-z0-9]+(-[a-z0-9]+)*$/';

    public const INVALID_NAME_MESSAGE = 'The container name is invalid.';

    public function __construct(private readonly string $value)
    {
        $this->validate($value);
    }

    private function  validate(string $value): void
    {
        if (false === preg_match(self::PATTERN, $value)) {
            $this->valid = false;
            $this->error = self::INVALID_NAME_MESSAGE;
        }
    }
}
