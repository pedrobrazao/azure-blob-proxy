<?php

declare(strict_types=1);

namespace App\Tests\Unit\Validator;

use App\Validator\RequiredArgumentValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RequiredArgumentValidatorTest extends TestCase
{
    #[DataProvider('argumentProvider')]
    public function testRequiredArgument(string $name, array $arguments, bool $valid): void
    {
        $validator = new RequiredArgumentValidator;

        $this->assertSame($valid, $validator->validate($name, $arguments)->isValid());
        $this->assertSame($valid ? null : RequiredArgumentValidator::MISSING_ARGUMENT_MESSAGE . $name, $validator->getError());
    }

    public static function argumentProvider(): array
    {
        return [
            ['op', ['op' => 'list'], true],
            ['op', [], false],
            ['', [], false],
        ];
   }
}