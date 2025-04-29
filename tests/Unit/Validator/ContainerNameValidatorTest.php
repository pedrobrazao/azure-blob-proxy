<?php

declare(strict_types=1);

namespace App\Tests\Unit\Validator;

use PHPUnit\Framework\Attributes\DataProvider;
use App\Validator\ContainerNameValidator;
use PHPUnit\Framework\TestCase;

final class ContainerNameValidatorTest extends TestCase
{
    #[DataProvider('containerNameProvider')]
    public function testContainerName(string $name, bool $valid): void
    {
        $validator = new ContainerNameValidator();

        $this->assertSame($valid, $validator->validate($name)->isValid());
        $this->assertSame($valid ? null : ContainerNameValidator::INVALID_NAME_MESSAGE . $name, $validator->getError());
    }

    public static function containerNameProvider(): array
    {
        return [
            ['my-container-name', true],
            ['abc123', true],
            ['xxx', true],
            [str_repeat('x', 63), true],
            ['Container Name', false],
            ['c', false],
            ['cc', false],
            [str_repeat('x', 64), false],
            ['-invalid', false],
            ['invalid-', false],
            ['invalid--name', false],
        ];
    }
}
