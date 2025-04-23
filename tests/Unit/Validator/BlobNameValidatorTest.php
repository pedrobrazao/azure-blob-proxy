<?php

declare(strict_types=1);

namespace App\Tests\Unit\Validator;

use App\Validator\BlobNameValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class BlobNameValidatorTest extends TestCase
{
    #[DataProvider('blobNameProvider')]
    public function testBlobName(string $name, bool $valid): void
    {
        $validator = new BlobNameValidator();

        $this->assertSame($valid, $validator->validate($name)->isValid());
        $this->assertSame($valid ? null : BlobNameValidator::INVALID_NAME_MESSAGE, $validator->getError());
    }

    public static function blobNameProvider(): array
    {
        return [
            ['foo.txt', true],
            ['foo/bar.txt', true],
            ['', false],
            [str_repeat('x', 255), false],
        ];
   }
}