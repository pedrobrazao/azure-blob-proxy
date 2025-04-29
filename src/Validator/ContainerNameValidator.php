<?php

declare(strict_types=1);

namespace App\Validator;

final class ContainerNameValidator extends AbstractValidator
{
    /**
     * Container name MUST be:
     *  - 3 to 63 Characters;
     * - Starts With Letter or Number;
     * - Contains Letters, Numbers, and Dash (-);
     * - Every Dash (-) Must Be Immediately Preceded and Followed by a Letter or Number
     */
    public const NAME_PATTERN = '/^(?=.{3,63}$)[a-z0-9]+(-[a-z0-9]+)*$/';

    public const INVALID_NAME_MESSAGE = 'The container name is invalid: ';

    public function validate($value, array $context = []): self
    {
        parent::validate($value);

        if (
            false === is_string($value)
            || !preg_match(self::NAME_PATTERN, $value)
        ) {
            $this->error = self::INVALID_NAME_MESSAGE . $value;

            return $this;
        }

        $this->valid = true;
        $this->error = null;

        return $this;
    }
}
